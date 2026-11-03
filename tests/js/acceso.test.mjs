/*
 * Spec 041 (D19): el botón para ver la contraseña del inicio de sesión. Mejora progresiva: nace oculto,
 * el script lo muestra y alterna el tipo del campo. El script es clásico, así que se carga con node:vm
 * en un contexto aparte, con un documento mínimo.
 *
 * Ejecutar: node --test tests/js/acceso.test.mjs
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const CODIGO = readFileSync(new URL('../../public/js/acceso.js', import.meta.url), 'utf8');

/** Un botón y su campo, como los del inicio de sesión. */
function pagina() {
    const atributos = { 'aria-controls': 'password', 'aria-pressed': 'false' };
    const oyentes = {};
    const boton = {
        hidden: true,
        getAttribute: (nombre) => atributos[nombre],
        setAttribute: (nombre, valor) => { atributos[nombre] = valor; },
        addEventListener: (evento, oyente) => { oyentes[evento] = oyente; },
        pulsar: () => oyentes.click(),
    };
    const campo = { type: 'password' };
    const document = {
        querySelector: (selector) => (selector === '.ver-clave' ? boton : null),
        getElementById: (id) => (id === 'password' ? campo : null),
    };
    return { boton, campo, document };
}

test('el script muestra el botón', () => {
    const { boton, document } = pagina();
    vm.runInNewContext(CODIGO, { document });
    assert.equal(boton.hidden, false);
});

test('alterna el tipo del campo y aria-pressed', () => {
    const { boton, campo, document } = pagina();
    vm.runInNewContext(CODIGO, { document });

    boton.pulsar();
    assert.equal(campo.type, 'text');
    assert.equal(boton.getAttribute('aria-pressed'), 'true');

    boton.pulsar();
    assert.equal(campo.type, 'password');
    assert.equal(boton.getAttribute('aria-pressed'), 'false');
});

test('no falla si la página no tiene el botón', () => {
    assert.doesNotThrow(() => vm.runInNewContext(CODIGO, { document: { querySelector: () => null, getElementById: () => null } }));
});
