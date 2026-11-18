// `npm audit --audit-level=high` con las excepciones aprobadas de docs/security.md.
//
// npm no permite ignorar un aviso concreto. Este script ejecuta `npm audit --json`, junta los
// avisos altos y críticos y falla si queda alguno que no sea una excepción vigente. Una excepción
// vencida vuelve a fallar sola, aunque nadie la retire. Una excepción cuyo aviso ya no aparece se
// anuncia para retirarla. Sin dependencias: solo Node.
//
// Uso: node .github/scripts/auditar-npm.mjs   (desde la raíz del repositorio)

import { execSync } from 'node:child_process';

// Aviso (GHSA) → excepción de docs/security.md y su vencimiento (AAAA-MM-DD, incluido).
const EXCEPCIONES = {
    'GHSA-fc5q-39xc-vjpf': { id: 'EX5', vence: '2026-12-31' }, // picomatch ≤ 4.0.7, ReDoS; sin versión corregida
};

const NIVELES = new Set(['high', 'critical']);

function ejecutarAudit() {
    try {
        return execSync('npm audit --json', { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
    } catch (error) {
        // npm audit sale con código 1 cuando hay vulnerabilidades; el JSON sigue en stdout.
        if (error.stdout) return error.stdout;
        throw error;
    }
}

const informe = JSON.parse(ejecutarAudit());
if (informe.error) {
    console.error(`npm audit falló: ${informe.error.summary ?? JSON.stringify(informe.error)}`);
    process.exit(2);
}

// Los avisos son las entradas de `via` que son objetos; las de texto apuntan a otro paquete.
const avisos = new Map();
for (const [paquete, vulnerabilidad] of Object.entries(informe.vulnerabilities ?? {})) {
    for (const via of vulnerabilidad.via) {
        if (typeof via !== 'object' || !NIVELES.has(via.severity)) continue;
        const ghsa = (via.url ?? '').split('/').pop();
        const aviso = avisos.get(ghsa) ?? { titulo: via.title, severidad: via.severity, paquetes: new Set() };
        aviso.paquetes.add(paquete);
        avisos.set(ghsa, aviso);
    }
}

const hoy = new Date().toISOString().slice(0, 10);
let fallos = 0;

for (const [ghsa, aviso] of avisos) {
    const excepcion = EXCEPCIONES[ghsa];
    const donde = [...aviso.paquetes].join(', ');
    if (excepcion && hoy <= excepcion.vence) {
        console.log(`Ignorado por ${excepcion.id} (vence ${excepcion.vence}): ${ghsa} ${aviso.severidad} — ${aviso.titulo} [${donde}]`);
        continue;
    }
    const motivo = excepcion ? `la excepción ${excepcion.id} venció el ${excepcion.vence}` : 'sin excepción';
    console.error(`FALLO (${motivo}): ${ghsa} ${aviso.severidad} — ${aviso.titulo} [${donde}]`);
    fallos++;
}

for (const [ghsa, excepcion] of Object.entries(EXCEPCIONES)) {
    if (!avisos.has(ghsa)) {
        console.log(`Aviso: ${ghsa} ya no aparece; retira ${excepcion.id} de docs/security.md y de este script.`);
    }
}

if (fallos > 0) {
    console.error(`${fallos} aviso(s) alto(s) o crítico(s) sin excepción vigente.`);
    process.exit(1);
}
console.log('npm audit: ningún aviso alto o crítico sin excepción vigente.');
