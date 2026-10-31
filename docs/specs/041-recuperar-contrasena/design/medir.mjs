#!/usr/bin/env node
// Mide un candidato de la 041 pantalla por pantalla: evaluate.mjs lee una página y un criterios.json,
// y la 041 tiene ocho páginas (seis pantallas y dos correos). Este script parte design/criterios.json
// por pantalla (sus KPIs más los de "pantalla": "*", con los anchos propios de la pantalla) y llama
// a evaluate.mjs con cada una. Lo usan /design-spec 041, /implement (sobre la build) y /review.
//
// Uso:  node medir.mjs <carpeta del candidato | URL base> --evaluate <ruta de evaluate.mjs> --out <dir>
// Código 0: todas las páginas pasan puertas y KPIs · 1: alguna falla · 2: uso.
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const argv = process.argv.slice(2);
const opt = (n) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : undefined; };
const base = argv[0];
const evaluate = opt('--evaluate');
const out = opt('--out');
if (!base || base.startsWith('--') || !evaluate || !out) {
  console.log('Uso: node medir.mjs <carpeta del candidato | URL base> --evaluate <evaluate.mjs> --out <dir>');
  process.exit(2);
}
const dir = path.dirname(fileURLToPath(import.meta.url));
const C = JSON.parse(fs.readFileSync(path.join(dir, 'criterios.json'), 'utf8'));
const { pantallas, kpis, ...comun } = C;
fs.mkdirSync(out, { recursive: true });

let fallos = 0;
const resumen = [];
for (const [id, p] of Object.entries(pantallas)) {
  const crit = { ...comun, widths: p.widths || C.widths, kpis: kpis.filter(k => k.pantalla === id || k.pantalla === '*') };
  const critPath = path.join(out, `criterios-${id}.json`);
  fs.writeFileSync(critPath, JSON.stringify(crit, null, 2));
  const target = /^https?:\/\//.test(base) ? `${base.replace(/\/$/, '')}/${p.url || p.file}` : path.join(base, p.file);
  const r = spawnSync(process.execPath, [evaluate, target, '--criteria', critPath, '--out', path.join(out, id)], { encoding: 'utf8' });
  const ok = r.status === 0;
  if (!ok) fallos++;
  resumen.push(`| ${id} | ${ok ? 'pasa' : `falla (código ${r.status})`} | ${path.join(out, id, 'report.md')} |`);
  process.stdout.write(`── ${id}: ${ok ? 'pasa' : 'FALLA'}\n${(r.stdout || '').trim()}\n${(r.stderr || '').trim()}\n`);
}
const md = ['| Pantalla | Resultado | Informe |', '|---|---|---|', ...resumen].join('\n');
fs.writeFileSync(path.join(out, 'resumen.md'), md + '\n');
console.log('\n' + md);
process.exit(fallos ? 1 : 0);
