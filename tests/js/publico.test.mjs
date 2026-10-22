/*
 * Spec 038 (D16, D18; /analyze A25, A27): el script común de las páginas públicas.
 *
 * `estadoAhora(horario, zona, fecha)` escribe la pastilla «¿Abierto ahora?» con el horario de
 * `config/clinica.php` (el JSON de `data-horario`) y la hora de Lázaro Cárdenas. Se prueba con
 * instantes fijos en UTC: America/Mexico_City está en UTC−6 todo el año (sin horario de verano
 * desde 2022). El script es clásico, así que se carga con node:vm en un contexto aparte.
 *
 * Ejecutar: node --test tests/js/
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

const { estadoAhora } = cargar().DentissaPublico;
// Hora de Lázaro Cárdenas → instante UTC (UTC−6).
const local = (fecha, hora) => new Date(`${fecha}T${hora}:00-06:00`);

test('abierto: cierra hoy', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '10:00')), 'Abierto ahora · cierra a las 20:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '19:59')), 'Abierto ahora · cierra a las 20:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '09:00')), 'Abierto ahora · cierra a las 14:00', 'sábado');
});

test('cerrado antes de abrir: abre hoy', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '08:00')), 'Cerrado ahora · abre hoy a las 9:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '00:00')), 'Cerrado ahora · abre hoy a las 9:00', 'la medianoche del lunes');
});

test('cerrado al terminar: abre mañana', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '20:00')), 'Cerrado ahora · abre mañana a las 9:00', 'el cierre exacto ya está cerrado');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-19', '21:00')), 'Cerrado ahora · abre mañana a las 9:00');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-23', '21:00')), 'Cerrado ahora · abre mañana a las 9:00', 'viernes: el sábado abre');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-25', '12:00')), 'Cerrado ahora · abre mañana a las 9:00', 'domingo: el lunes abre');
});

test('cerrado más de un día: abre el lunes', () => {
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '14:00')), 'Cerrado ahora · abre el lunes a las 9:00', 'sábado al cerrar');
    assert.equal(estadoAhora(HORARIO, ZONA, local('2026-10-24', '15:00')), 'Cerrado ahora · abre el lunes a las 9:00');
});

test('usa la hora de Lázaro Cárdenas, no la del navegador ni la UTC', () => {
    // Martes 02:30 en UTC es lunes 20:30 en Lázaro Cárdenas.
    assert.equal(estadoAhora(HORARIO, ZONA, new Date('2026-10-20T02:30:00Z')), 'Cerrado ahora · abre mañana a las 9:00');
    // Lunes 15:30 en UTC es lunes 9:30 en Lázaro Cárdenas.
    assert.equal(estadoAhora(HORARIO, ZONA, new Date('2026-10-19T15:30:00Z')), 'Abierto ahora · cierra a las 20:00');
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

/** Elemento falso con lo justo para escribir la pastilla: hijos, textContent y su documento. */
function elementoFalso() {
    const crear = (etiqueta) => ({
        tagName: etiqueta.toUpperCase(), className: '', textContent: '', hijos: [],
        appendChild(hijo) { this.hijos.push(hijo); return hijo; },
        set innerHTML(_) { throw new Error('innerHTML no se usa'); },
    });
    const el = crear('span');
    el.ownerDocument = { createElement: crear };
    Object.defineProperty(el, 'textContent', {
        get() { return this.hijos.map((h) => h.textContent).join(''); },
        set(valor) { if (valor !== '') throw new Error('se escribe por partes'); this.hijos = []; },
    });
    return el;
}

test('la pastilla se escribe en tres partes para partirse en el móvil (refinado)', () => {
    const { escribirPastilla } = cargar().DentissaPublico;
    assert.equal(typeof escribirPastilla, 'function', 'publico.js expone escribirPastilla');
    const el = elementoFalso();
    escribirPastilla(el, 'Cerrado ahora · abre mañana a las 9:00');
    assert.deepEqual(el.hijos.map((h) => [h.tagName, h.className, h.textContent]), [
        ['SPAN', '', 'Cerrado ahora'],
        ['SPAN', 'estado-sep', ' · '],
        ['SPAN', '', 'abre mañana a las 9:00'],
    ]);
    assert.equal(el.textContent, 'Cerrado ahora · abre mañana a las 9:00', 'el texto entero no cambia');
});
