/*
 * Script común de las páginas públicas (spec 038, D16). Se carga con defer y la página está completa
 * sin él: añade la pastilla «¿Abierto ahora?», las entradas al bajar (clase is-in) y el botón de pausa
 * de la franja (WCAG 2.2.2). La clase js de <html>, que activa las entradas y la franja en
 * publico.css, se pone al final y solo con movimiento normal e IntersectionObserver. Escribe siempre
 * con textContent; sin dependencias ni HTML construido desde cadenas.
 */
(function (global) {
    'use strict';

    var DIAS = { 1: 'lunes', 2: 'martes', 3: 'miércoles', 4: 'jueves', 5: 'viernes', 6: 'sábado', 7: 'domingo' };
    var SEMANA = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 };
    var HORA = /^(\d{1,2}):(\d{2})$/;

    function minutos(hora) {
        var m = HORA.exec(hora);
        return m ? Number(m[1]) * 60 + Number(m[2]) : NaN;
    }

    // Día ISO y minutos del día en la zona de la consulta (no en la del navegador).
    function ahoraEn(zona, fecha) {
        var partes = {};
        new Intl.DateTimeFormat('en-US', { timeZone: zona, weekday: 'short', hour: 'numeric', minute: 'numeric', hourCycle: 'h23' })
            .formatToParts(fecha)
            .forEach(function (p) { partes[p.type] = p.value; });
        return { dia: SEMANA[partes.weekday], minuto: (Number(partes.hour) % 24) * 60 + Number(partes.minute) };
    }

    // Horario: el JSON de data-horario (día ISO → [apertura, cierre]). Devuelve el texto o null.
    function estadoAhora(horario, zona, fecha) {
        try {
            var datos = JSON.parse(horario);
            if (!datos || typeof datos !== 'object' || Array.isArray(datos)) return null;
            var dias = Object.keys(datos);
            if (!dias.length) return null;
            for (var i = 0; i < dias.length; i++) {
                var tramo = datos[dias[i]];
                if (!DIAS[dias[i]] || !Array.isArray(tramo) || tramo.length !== 2 || !(minutos(tramo[0]) < minutos(tramo[1]))) return null;
            }
            if (Object.prototype.toString.call(fecha) !== '[object Date]' || isNaN(fecha.getTime())) return null;

            var ahora = ahoraEn(zona, fecha);
            var hoy = datos[ahora.dia];
            if (hoy && ahora.minuto >= minutos(hoy[0]) && ahora.minuto < minutos(hoy[1])) {
                return 'Abierto ahora · cierra a las ' + hoy[1];
            }
            for (var k = 0; k < 8; k++) {
                var dia = ((ahora.dia - 1 + k) % 7) + 1;
                var abre = datos[dia];
                if (!abre || (k === 0 && ahora.minuto >= minutos(abre[0]))) continue;
                var cuando = k === 0 ? 'hoy' : k === 1 ? 'mañana' : 'el ' + DIAS[dia];
                return 'Cerrado ahora · abre ' + cuando + ' a las ' + abre[0];
            }
            return null;
        } catch (e) {
            // JSON roto o zona que Intl no conoce: sin pastilla.
            return null;
        }
    }

    // Escribe el texto en tres span (estado, « · » y hora) para que publico.css lo parta en dos
    // líneas por debajo de 560 px (refinado de design.md); el textContent junto es el texto entero.
    function escribirPastilla(elemento, texto) {
        var corte = texto.indexOf(' · ');
        var partes = corte < 0 ? [texto] : [texto.slice(0, corte), ' · ', texto.slice(corte + 3)];
        elemento.textContent = '';
        partes.forEach(function (parte, i) {
            var span = elemento.ownerDocument.createElement('span');
            if (i === 1) span.className = 'estado-sep';
            span.textContent = parte;
            elemento.appendChild(span);
        });
    }

    global.DentissaPublico = { estadoAhora: estadoAhora, escribirPastilla: escribirPastilla };

    var doc = global.document;
    if (!doc) return;

    function iniciar() {
        var vivo = doc.querySelector('[data-live]');
        var pastilla = vivo && vivo.querySelector('.estado-ahora');
        var texto = vivo && estadoAhora(vivo.getAttribute('data-horario'), vivo.getAttribute('data-zona'), new Date());
        if (pastilla && texto) escribirPastilla(pastilla, texto);

        // Con movimiento reducido o sin IntersectionObserver, nada se oculta ni se mueve.
        var reducido = typeof global.matchMedia === 'function' && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducido || typeof global.IntersectionObserver !== 'function') return;

        var franja = doc.querySelector('.marquesina');
        var pausa = doc.querySelector('.marquesina-pausa');
        if (franja && pausa) {
            pausa.hidden = false;
            pausa.addEventListener('click', function () {
                var pausada = pausa.getAttribute('aria-pressed') !== 'true';
                pausa.setAttribute('aria-pressed', String(pausada));
                franja.classList.toggle('is-pausada', pausada);
            });
        }

        var observador = new global.IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) return;
                entrada.target.classList.add('is-in');
                observador.unobserve(entrada.target);
            });
        }, { rootMargin: '0px 0px -12% 0px' });
        Array.prototype.forEach.call(doc.querySelectorAll('[data-reveal]'), function (el) {
            // Escalonado por orden entre hermanos, cuatro pasos como máximo (design.md, M-3).
            var orden = Array.prototype.indexOf.call(el.parentNode.children, el);
            el.style.setProperty('--i', String(Math.min(orden, 3)));
            observador.observe(el);
        });

        doc.documentElement.classList.add('js');
    }

    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})(globalThis);
