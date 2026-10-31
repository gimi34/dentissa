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
 * `progresoEscena(rect, alto)`, el progreso --p de la escena de la ventana.
 *
 * Ejecutar: node --test tests/js/publico.test.mjs
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const CODIGO = readFileSync(new URL('../../public/js/publico.js', import.meta.url), 'utf8');
const ZONA = 'America/Mexico_City';
// El de config/clinica.php (contacto.horario_semanal), como lo escribe @json en data-horario.
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
    // La escena mide 1.4 pantallas (data-hold): con la ventana de 800 px, 1120 px y 320 de recorrido.
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
