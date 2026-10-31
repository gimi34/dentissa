/*
 * Script común de las páginas públicas (spec 038, D16; ronda 5, D19). Se carga con defer y la página
 * está completa sin él. Escribe la pastilla «¿Está abierto ahora?» y marca la fila de hoy de la
 * semana; pone el filete de la cabecera al bajar; cierra el menú del móvil con Escape (devolviendo el
 * foco a «Menú») y al elegir un enlace. Solo con movimiento normal e IntersectionObserver: las
 * entradas al bajar (clase is-in), el progreso --p de la escena de la ventana (sin capturar la rueda)
 * y la clase js de <html>, que se pone al final. Escribe siempre con textContent; sin dependencias ni
 * HTML construido desde cadenas.
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

    function esFecha(fecha) {
        return Object.prototype.toString.call(fecha) === '[object Date]' && !isNaN(fecha.getTime());
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
            if (!esFecha(fecha)) return null;

            var ahora = ahoraEn(zona, fecha);
            var hoy = datos[ahora.dia];
            if (hoy && ahora.minuto >= minutos(hoy[0]) && ahora.minuto < minutos(hoy[1])) {
                return 'Abierto ahora · cerramos a las ' + hoy[1];
            }
            for (var k = 0; k < 8; k++) {
                var dia = ((ahora.dia - 1 + k) % 7) + 1;
                var abre = datos[dia];
                if (!abre || (k === 0 && ahora.minuto >= minutos(abre[0]))) continue;
                var cuando = k === 0 ? 'hoy' : k === 1 ? 'mañana' : 'el ' + DIAS[dia];
                return 'Cerrado · abrimos ' + cuando + ' a las ' + abre[0];
            }
            return null;
        } catch (e) {
            // JSON roto o zona que Intl no conoce: sin pastilla.
            return null;
        }
    }

    // La fila de la semana de hoy (su data-day): «1-5» de lunes a viernes, «6» el sábado y «0» el
    // domingo, con la hora de la consulta (/analyze A39). null si los datos no valen.
    function diaDeHoy(zona, fecha) {
        try {
            if (!esFecha(fecha)) return null;
            var dia = ahoraEn(zona, fecha).dia;
            if (!dia) return null;
            return dia === 7 ? '0' : dia === 6 ? '6' : '1-5';
        } catch (e) {
            return null;
        }
    }

    // Progreso de la escena (0 → 1) mientras su caja recorre la pantalla: rect es su
    // getBoundingClientRect() y alto el de la ventana (/analyze A39). Sin recorrido, la foto entera.
    function progresoEscena(rect, alto) {
        var recorrido = rect.height - alto;
        if (!(recorrido > 0) || isNaN(rect.top)) return 1;
        return Math.min(1, Math.max(0, -rect.top / recorrido));
    }

    global.DentissaPublico = { estadoAhora: estadoAhora, diaDeHoy: diaDeHoy, progresoEscena: progresoEscena };

    var doc = global.document;
    if (!doc) return;

    function iniciar() {
        var ahora = new Date();
        var vivo = doc.querySelector('[data-live]');
        var zona = vivo && vivo.getAttribute('data-zona');
        var texto = vivo && estadoAhora(vivo.getAttribute('data-horario'), zona, ahora);
        var nodo = vivo && vivo.querySelector('.pub-status__text');
        if (nodo && texto) {
            nodo.textContent = texto;
            vivo.setAttribute('data-open', String(texto.indexOf('Abierto') === 0));
        }
        var hoy = zona && diaDeHoy(zona, ahora);
        Array.prototype.forEach.call(doc.querySelectorAll('[data-semana] [data-day]'), function (fila) {
            if (fila.getAttribute('data-day') === hoy) fila.setAttribute('data-today', '');
        });

        // Cabecera: el filete aparece al bajar.
        var barra = doc.querySelector('.pub-bar');
        if (barra && typeof global.addEventListener === 'function') {
            var alBajar = function () { barra.classList.toggle('is-scrolled', global.scrollY > 8); };
            global.addEventListener('scroll', alBajar, { passive: true });
            alBajar();
        }

        // Menú del móvil: Escape lo cierra y devuelve el foco a «Menú»; elegir un enlace también lo cierra.
        var menu = doc.querySelector('.pub-menu');
        if (menu) {
            menu.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape' || !menu.open) return;
                menu.open = false;
                menu.querySelector('summary').focus();
            });
            Array.prototype.forEach.call(menu.querySelectorAll('a'), function (a) {
                a.addEventListener('click', function () { menu.open = false; });
            });
        }

        // Con movimiento reducido o sin IntersectionObserver, nada se oculta ni se mueve.
        var reducido = typeof global.matchMedia === 'function' && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducido || typeof global.IntersectionObserver !== 'function') return;

        var observador = new global.IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) return;
                entrada.target.classList.add('is-in');
                observador.unobserve(entrada.target);
            });
        }, { rootMargin: '0px 0px -12% 0px' });
        var grupos = [];
        Array.prototype.forEach.call(doc.querySelectorAll('[data-reveal]'), function (el) {
            // Escalonado por sección, cuatro pasos como máximo (design.md, M-4).
            var seccion = el.closest('section') || doc.body;
            var grupo = grupos.filter(function (g) { return g.seccion === seccion; })[0];
            if (!grupo) grupos.push(grupo = { seccion: seccion, n: 0 });
            el.style.setProperty('--i', String(Math.min(grupo.n++, 3)));
            observador.observe(el);
        });

        // La escena de la ventana (M-1): --p con el progreso del desplazamiento nativo.
        var escenas = Array.prototype.slice.call(doc.querySelectorAll('[data-scene]'));
        escenas.forEach(function (escena) {
            escena.style.setProperty('--p', '0');
            escena.classList.add('is-live');
        });
        if (escenas.length && typeof global.requestAnimationFrame === 'function') {
            var ultimo = -1;
            var paso = function () {
                if (global.scrollY !== ultimo) {
                    ultimo = global.scrollY;
                    escenas.forEach(function (escena) {
                        var r = escena.getBoundingClientRect();
                        if (r.bottom < 0 || r.top > global.innerHeight) return;
                        escena.style.setProperty('--p', progresoEscena(r, global.innerHeight).toFixed(4));
                    });
                }
                global.requestAnimationFrame(paso);
            };
            global.requestAnimationFrame(paso);
        }

        doc.documentElement.classList.add('js');
    }

    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})(globalThis);
