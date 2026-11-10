/*
 * Spec 038 (D16, D18; /analyze A25, A27, A39): el script común de las páginas públicas.
 *
 * `estadoAhora(horario, zona, fecha)` escribe la pastilla «¿Está abierto ahora?» con el horario de
 * `config/clinica.php` (el JSON de `data-horario`) y la hora de Lázaro Cárdenas. Se prueba con
 * instantes fijos en UTC: America/Mexico_City está en UTC−6 todo el año (sin horario de verano
 * desde 2022). El script es clásico, así que se carga con node:vm en un contexto aparte.
 *
 * Ronda 5 (D19): los textos van en primera persona («Abierto ahora · cerramos a las 20:00»,
 * «Cerrado · abrimos mañana a las 9:00»), en un solo nodo; sale `escribirPastilla()` (D21) y entran
 * dos funciones puras (A39): `diaDeHoy(zona, fecha)`, la fila de la semana que se marca, y
 * `progresoEscena(rect, alto)`, el progreso --p de la escena (en la ronda 6, la de la consulta).
 *
 * Ejecutar: node --test tests/js/publico.test.mjs
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const CODIGO = readFileSync(new URL('../../public/js/publico.js', import.meta.url), 'utf8');
const ZONA = 'America/Mexico_City';
// El de config/clinica.php (contacto.horario_semanal), como lo escribe json_encode en data-horario.
const HORARIO = JSON.stringify({ 1: ['9:00', '20:00'], 2: ['9:00', '20:00'], 3: ['9:00', '20:00'], 4: ['9:00', '20:00'], 5: ['9:00', '20:00'], 6: ['9:00', '14:00'] });

/** Carga el script en un contexto nuevo con los globales que se le pasen. */
function cargar(globales = {}) {
    const contexto = vm.createContext({ ...globales });
    vm.runInContext(CODIGO, contexto);
    return contexto;
}

const { estadoAhora, diaDeHoy, progresoEscena } = cargar().DentissaPublico;
// Hora de Lázaro Cárdenas → instante UTC (UTC−6).
const local = (fecha, hora) => new Date(`${fecha}T${hora}:00-06:00`);

test('abierto: cerramos hoy', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '10:00')), 'Abierto ahora · cerramos a las 20:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '19:59')), 'Abierto ahora · cerramos a las 20:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '09:00')), 'Abierto ahora · cerramos a las 14:00', 'sábado');
});

test('cerrado antes de abrir: abrimos hoy', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '08:00')), 'Cerrado · abrimos hoy a las 9:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '00:00')), 'Cerrado · abrimos hoy a las 9:00', 'la medianoche del lunes');
});

test('cerrado al terminar: abrimos mañana', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '20:00')), 'Cerrado · abrimos mañana a las 9:00', 'el cierre exacto ya está cerrado');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '21:00')), 'Cerrado · abrimos mañana a las 9:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-23', '21:00')), 'Cerrado · abrimos mañana a las 9:00', 'viernes: el sábado abre');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-25', '12:00')), 'Cerrado · abrimos mañana a las 9:00', 'domingo: el lunes abre');
});

test('cerrado más de un día: abrimos el lunes', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '14:00')), 'Cerrado · abrimos el lunes a las 9:00', 'sábado al cerrar');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '15:00')), 'Cerrado · abrimos el lunes a las 9:00');
});

test('usa la hora de Lázaro Cárdenas, no la del navegador ni la UTC', () => {
    // Martes 02:30 en UTC es lunes 20:30 en Lázaro Cárdenas.
    assert.equal(estadoAhora(HORARIO, ZONA, new Date('2026-10-20T02:30:00Z')), 'Cerrado · abrimos mañana a las 9:00');
    // Lunes 15:30 en UTC es lunes 9:30 en Lázaro Cárdenas.
    assert.equal(estadoAhora(HORARIO, ZONA, new Date('2026-10-19T15:30:00Z')), 'Abierto ahora · cerramos a las 20:00');
});

test('con datos que no valen devuelve null sin lanzar (A27)', () => {
    const ahora = local('2026-10-19', '10:00');
    assert.equal(estadoAhora('{roto', ZONA, ahora), null, 'JSON roto');
    assert.equal(estadoAhora('"texto"', ZONA, ahora), null, 'JSON que no es un horario');
    assert.equal(estadoAhora(HORARIO, 'Zona/Inventada', ahora), null, 'zona inválida');
    assert.equal(estadoAhora(HORARIO, ZONA, new Date('no es una fecha')), null, 'fecha inválida');
    assert.equal(estadoAhora(JSON.stringify({ 1: ['9:00'] }), ZONA, ahora), null, 'día sin cierre');
    assert.equal(estadoAhora('{}', ZONA, ahora), null, 'sin ningún día abierto');
});

test('la fila de hoy de la semana, con la hora de Lázaro Cárdenas (A39)', () => {
    // data-day de la semana: «1-5» (lunes a viernes), «6» (sábados) y «0» (domingos).
    assert.equal(diaDeHoy(ZONA, local('2026-10-19', '10:00')), '1-5', 'lunes');
    assert.equal(diaDeHoy(ZONA, local('2026-10-23', '23:59')), '1-5', 'viernes');
    assert.equal(diaDeHoy(ZONA, local('2026-10-24', '00:00')), '6', 'sábado');
    assert.equal(diaDeHoy(ZONA, local('2026-10-25', '12:00')), '0', 'domingo');
    // Lunes 03:00 en UTC es domingo 21:00 en Lázaro Cárdenas.
    assert.equal(diaDeHoy(ZONA, new Date('2026-10-26T03:00:00Z')), '0', 'no la UTC');
    assert.equal(diaDeHoy('Zona/Inventada', local('2026-10-19', '10:00')), null, 'zona inválida');
    assert.equal(diaDeHoy(ZONA, new Date('no es una fecha')), null, 'fecha inválida');
});

test('el progreso de la escena va de 0 a 1 con el desplazamiento (A39)', () => {
    // Una escena de 1120 px en una pantalla de 800: 320 px de recorrido. (La de la consulta mide 1.8
    // pantallas en publico.css; la cuenta es la misma.)
    const alto = 800;
    assert.equal(progresoEscena({ top: 200, height: 1120 }, alto), 0, 'antes de llegar');
    assert.equal(progresoEscena({ top: 0, height: 1120 }, alto), 0, 'al llegar arriba');
    assert.equal(progresoEscena({ top: -160, height: 1120 }, alto), 0.5, 'a la mitad del recorrido');
    assert.equal(progresoEscena({ top: -320, height: 1120 }, alto), 1, 'al final');
    assert.equal(progresoEscena({ top: -900, height: 1120 }, alto), 1, 'después');
    assert.equal(progresoEscena({ top: -50, height: 600 }, alto), 1, 'sin recorrido (más baja que la ventana): la foto entera');
    assert.equal(progresoEscena({ top: Number.NaN, height: 1120 }, alto), 1, 'datos que no valen: la foto entera');
});

/** Documento mínimo: lo justo para ver qué clases pone el script en <html>. */
function documentoFalso() {
    const clases = new Set();
    return {
        clases,
        document: {
            readyState: 'complete',
            documentElement: { classList: { add: (c) => clases.add(c), contains: (c) => clases.has(c) } },
            querySelector: () => null,
            querySelectorAll: () => [],
            addEventListener: () => {},
        },
        matchMedia: () => ({ matches: false }),
    };
}

test('sin IntersectionObserver no pone la clase js ni oculta nada (A27)', () => {
    const falso = documentoFalso();
    cargar({ document: falso.document, matchMedia: falso.matchMedia });
    assert.equal(falso.clases.has('js'), false);
});

test('con IntersectionObserver pone la clase js al terminar (A27)', () => {
    const falso = documentoFalso();
    class IntersectionObserver { observe() {} unobserve() {} disconnect() {} }
    cargar({ document: falso.document, matchMedia: falso.matchMedia, IntersectionObserver });
    assert.equal(falso.clases.has('js'), true);
});

test('sin el botón de pausa ni escribirPastilla (D21)', () => {
    const publico = cargar().DentissaPublico;
    assert.equal(publico.escribirPastilla, undefined);
    assert.doesNotMatch(CODIGO, /marquesina/, 'el script ya no conoce la franja');
});

/*
 * Spec 039 (T018; CA1, RNF de accesibilidad; M-1): el carrusel de «Nuestros trabajos». Dos funciones
 * puras, `fotoVecina(actual, paso, total)` y `textoEstado(hijos)`, y el montaje sobre un carrusel
 * falso con el marcado de `c3/inicio.html`. Sin avance automático (WCAG 2.2.2).
 */
const oculto = (texto) => ({ textContent: texto, getAttribute: (a) => (a === 'aria-hidden' ? 'true' : null) });
const visible = (texto) => ({ textContent: texto, getAttribute: () => null });

test('la foto vecina da la vuelta en los dos sentidos (039)', () => {
    const { fotoVecina } = cargar().DentissaPublico;
    assert.equal(fotoVecina(0, 1, 6), 1);
    assert.equal(fotoVecina(2, -1, 6), 1);
    assert.equal(fotoVecina(5, 1, 6), 0, 'de la última a la primera');
    assert.equal(fotoVecina(0, -1, 6), 5, 'de la primera a la última');
    assert.equal(fotoVecina(0, 1, 1), 0, 'con una sola foto se queda');
    assert.equal(fotoVecina(Number.NaN, 1, 6), 0, 'datos que no valen: la primera');
});

test('el texto de estado no lee el numeral decorativo (039)', () => {
    const { textoEstado } = cargar().DentissaPublico;
    assert.equal(textoEstado([oculto('02'), visible('Foto 2 de 6'), visible('  Corona de\n porcelana  en un premolar. ')]), 'Foto 2 de 6 Corona de porcelana en un premolar.');
    assert.equal(textoEstado([]), '');
});

/** Un carrusel falso con tres fotos, con los selectores de `c3/inicio.html`. */
function carruselFalso() {
    const enlace = () => ({ href: '', setAttribute(k, v) { if (k === 'href') this.href = v; }, addEventListener(t, f) { this[t] = f; } });
    const items = [1, 2, 3].map((n) => ({
        id: `trabajo-${n}`,
        offsetLeft: (n - 1) * 300,
        classList: { toggle() {} },
        querySelector: (s) => (s === 'figcaption' ? { children: [oculto(`0${n}`), visible(`Foto ${n} de 3`), visible(`Trabajo ${n}`)] } : null),
    }));
    const ventana = { scrollLeft: 0, desplazado: [], scrollTo(o) { this.desplazado.push(o); }, addEventListener() {} };
    const partes = { '.pub-carrusel__ventana': ventana, '[data-anterior]': enlace(), '[data-siguiente]': enlace(), '[data-estado]': { textContent: '' } };
    const carrusel = { querySelector: (s) => partes[s] ?? null, querySelectorAll: (s) => (s === '.pub-carrusel__lista > li' ? items : []) };
    const falso = documentoFalso();
    falso.document.querySelectorAll = (s) => (s === '[data-carrusel]' ? [carrusel] : []);
    return { falso, partes, ventana };
}

test('el carrusel apunta a las vecinas, anuncia la foto y no avanza solo (039)', () => {
    const { falso, partes, ventana } = carruselFalso();
    let temporizadores = 0;
    const contar = () => { temporizadores++; return 0; };
    cargar({ document: falso.document, matchMedia: () => ({ matches: false }), setTimeout: contar, setInterval: contar });

    assert.equal(partes['[data-siguiente]'].href, '#trabajo-2');
    assert.equal(partes['[data-anterior]'].href, '#trabajo-3', 'desde la primera, «Anterior» va a la última');
    assert.equal(partes['[data-estado]'].textContent, '', 'la región de estado empieza vacía');
    assert.equal(temporizadores, 0, 'ningún temporizador al montar: no avanza solo');

    partes['[data-siguiente]'].click({ preventDefault() {} });
    assert.equal(partes['[data-estado]'].textContent, 'Foto 2 de 3 Trabajo 2', 'anuncia la foto sin el numeral');
    assert.equal(ventana.desplazado.at(-1).left, 300);
    assert.equal(ventana.desplazado.at(-1).behavior, 'smooth');
    assert.equal(partes['[data-siguiente]'].href, '#trabajo-3');
    assert.equal(partes['[data-anterior]'].href, '#trabajo-1');
    assert.equal(temporizadores, 0, 'pulsar tampoco programa un avance');
    assert.doesNotMatch(CODIGO, /setInterval/, 'el script no tiene ningún avance periódico');
});

test('con movimiento reducido el carrusel salta sin animar (039)', () => {
    const { falso, partes, ventana } = carruselFalso();
    cargar({ document: falso.document, matchMedia: (q) => ({ matches: q === '(prefers-reduced-motion: reduce)' }), setTimeout: () => 0 });

    partes['[data-anterior]'].click({ preventDefault() {} });
    assert.equal(ventana.desplazado.at(-1).behavior, 'auto');
    assert.equal(ventana.desplazado.at(-1).left, 600, 'de la primera a la última');
});

test('con una sola foto, queda marcada como actual y no se monta nada más (039, /review R8)', () => {
    const clases = new Set();
    const unica = { id: 'trabajo-1', offsetLeft: 0, classList: { toggle: (c, si) => (si ? clases.add(c) : clases.delete(c)), add: (c) => clases.add(c) }, querySelector: () => null };
    const enlace = () => ({ href: '#trabajo-1', setAttribute(k, v) { if (k === 'href') this.href = v; }, addEventListener(t, f) { this[t] = f; } });
    const partes = { '.pub-carrusel__ventana': { scrollTo() {}, addEventListener() {} }, '[data-anterior]': enlace(), '[data-siguiente]': enlace(), '[data-estado]': { textContent: '' } };
    const carrusel = { querySelector: (s) => partes[s] ?? null, querySelectorAll: (s) => (s === '.pub-carrusel__lista > li' ? [unica] : []) };
    const falso = documentoFalso();
    falso.document.querySelectorAll = (s) => (s === '[data-carrusel]' ? [carrusel] : []);
    cargar({ document: falso.document, matchMedia: () => ({ matches: false }), setTimeout: () => 0 });

    assert.ok(clases.has('is-actual'), 'la única foto es la actual: sin escala fija');
    assert.equal(partes['[data-siguiente]'].click, undefined, 'sin vecinas, los enlaces no se enganchan');
});

/*
 * Spec 044 (CA6, T036; /review R2, R6): el aviso de la cabecera («Has cerrado sesión»). Su texto va en una
 * región role="status" que llega con el texto del servidor (sin JavaScript se ve igual); el script la vacía y la
 * vuelve a escribir un instante después, que es el cambio que anuncian los lectores de pantalla. A los 6 s se
 * retira: con movimiento normal se funde (clase is-saliendo) y se oculta al terminar la transición o, si no
 * llega, con un temporizador de respaldo; con movimiento reducido o sin la clase js, sin fundido. Sin aviso no
 * se programa nada.
 */
const TEXTO = 'Has cerrado sesión';

function documentoConAviso(reducido, conJs = true) {
    const temporizadores = [];
    const oyentes = {};
    const clasesAviso = new Set();
    const region = { textContent: TEXTO };
    const aviso = {
        hidden: false,
        classList: { add: (c) => clasesAviso.add(c), contains: (c) => clasesAviso.has(c) },
        addEventListener: (tipo, fn) => { oyentes[tipo] = fn; },
        querySelector: (sel) => (sel === '[role="status"]' ? region : null),
    };
    return {
        aviso,
        region,
        clasesAviso,
        oyentes,
        temporizadores,
        globales: {
            document: {
                readyState: 'complete',
                documentElement: { classList: { add: () => {}, contains: (c) => c === 'js' && conJs } },
                querySelector: (sel) => (sel === '.pub-aviso' ? aviso : null),
                querySelectorAll: () => [],
                addEventListener: () => {},
            },
            matchMedia: () => ({ matches: reducido }),
            setTimeout: (fn, ms) => temporizadores.push({ fn, ms }),
        },
    };
}

/** Ejecuta el primer temporizador pendiente con esa espera. */
function correr(falso, ms) {
    const t = falso.temporizadores.find((x) => x.ms === ms && !x.hecho);
    assert.ok(t, `hay un temporizador de ${ms} ms`);
    t.hecho = true;
    t.fn();
}

test('el texto del aviso se reescribe tras la carga para que se anuncie (spec 044, R2)', () => {
    const falso = documentoConAviso(false);
    cargar(falso.globales);

    assert.equal(falso.region.textContent, '', 'al cargar, la región se vacía');
    const anuncio = falso.temporizadores.find((t) => t.ms < 1000);
    assert.ok(anuncio, 'y se programa volver a escribirla enseguida');
    anuncio.fn();
    assert.equal(falso.region.textContent, TEXTO, 'el mismo texto, ahora como cambio de la región');
});

test('el aviso de la cabecera se retira solo a los 6 s, con fundido (spec 044)', () => {
    const falso = documentoConAviso(false);
    cargar(falso.globales);

    assert.equal(falso.temporizadores.filter((t) => t.ms === 6000).length, 1, 'un solo temporizador de retirada');
    assert.equal(falso.aviso.hidden, false, 'antes de los 6 s se ve');
    correr(falso, 6000);
    assert.equal(falso.clasesAviso.has('is-saliendo'), true, 'empieza el fundido');
    assert.equal(falso.aviso.hidden, false, 'sigue en la página mientras se funde');
    falso.oyentes.transitionend();
    assert.equal(falso.aviso.hidden, true, 'al terminar el fundido se oculta');
});

test('si no llega transitionend, un respaldo oculta el aviso (spec 044, R6)', () => {
    const falso = documentoConAviso(false);
    cargar(falso.globales);

    correr(falso, 6000);
    const respaldo = falso.temporizadores.find((t) => !t.hecho && t.ms > 0 && t.ms < 6000 && t !== falso.temporizadores[0]);
    assert.ok(respaldo, 'al empezar el fundido se programa un respaldo');
    respaldo.fn();
    assert.equal(falso.aviso.hidden, true, 'el respaldo lo oculta sin transitionend');
});

test('con movimiento reducido el aviso se oculta sin fundido (spec 044)', () => {
    const falso = documentoConAviso(true);
    cargar(falso.globales);

    correr(falso, 6000);
    assert.equal(falso.clasesAviso.has('is-saliendo'), false);
    assert.equal(falso.aviso.hidden, true);
});

test('sin aviso no se programa nada (spec 044)', () => {
    const falso = documentoConAviso(false);
    falso.globales.document.querySelector = () => null;
    cargar(falso.globales);

    assert.equal(falso.temporizadores.length, 0);
});

test('sin la clase js (sin fundido posible) el aviso se oculta directamente (spec 044)', () => {
    const falso = documentoConAviso(false, false);
    cargar(falso.globales);

    correr(falso, 6000);
    assert.equal(falso.clasesAviso.has('is-saliendo'), false);
    assert.equal(falso.aviso.hidden, true);
});
