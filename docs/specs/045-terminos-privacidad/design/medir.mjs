#!/usr/bin/env node
// Mide un candidato de la 045 pantalla por pantalla: evaluate.mjs lee una página y un criterios.json, y la 045 tiene
// dos páginas (términos y aviso de privacidad). Parte design/criterios.json por pantalla (sus KPIs más los de
// "pantalla": "*") y llama a evaluate.mjs con cada una (copia del de la 039). Lo usan /design-spec 045, /implement
// (sobre la build) y /review.
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
const solo = opt('--solo') ? opt('--solo').split(',') : null;   // --solo inicio,galeria: solo esas pantallas
for (const [id, p] of Object.entries(pantallas)) {
  if (solo && !solo.includes(id)) continue;
  // gates: ajustes propios de la pantalla; quitar: expresión de una pieza del layout que no diseña esta spec,
  // que se quita de la copia medida; sin_iframes: KPIs que se miden aparte con los iframes en blanco (el mapa
  // de Google queda fuera del peso por el RNF de la 038 y la 039).
  const crit = { ...comun, mode: p.mode || C.mode, gates: { ...comun.gates, ...(p.gates || {}) }, widths: p.widths || C.widths, kpis: kpis.filter(k => (k.pantalla === id || k.pantalla === '*') && !(p.sin_iframes || []).includes(k.id)) };
  const critPath = path.join(out, `criterios-${id}.json`);
  fs.writeFileSync(critPath, JSON.stringify(crit, null, 2));
  const target = /^https?:\/\//.test(base) ? `${base.replace(/\/$/, '')}/${p.url || p.file}` : path.join(base, p.file);
  let medido = target;
  if (p.quitar && !/^https?:\/\//.test(base)) {
    medido = path.join(base, `.medir-${p.file}`);
    fs.writeFileSync(medido, fs.readFileSync(target, 'utf8').replace(new RegExp(p.quitar), ''));
  }
  const r = spawnSync(process.execPath, [evaluate, medido, '--criteria', critPath, '--out', path.join(out, id)], { encoding: 'utf8' });
  if (medido !== target) fs.rmSync(medido, { force: true });
  let ok = r.status === 0;
  if (p.sin_iframes && !/^https?:\/\//.test(base)) {
    const sinMapa = path.join(base, `.sin-iframes-${p.file}`);
    fs.writeFileSync(sinMapa, fs.readFileSync(target, 'utf8').replace(/(<iframe[^>]*?)src="[^"]*"/g, '$1src="about:blank"'));
    const critPeso = path.join(out, `criterios-${id}-sin-iframes.json`);
    fs.writeFileSync(critPeso, JSON.stringify({ ...crit, kpis: kpis.filter(k => p.sin_iframes.includes(k.id)) }, null, 2));
    const r2 = spawnSync(process.execPath, [evaluate, sinMapa, '--criteria', critPeso, '--out', path.join(out, `${id}-sin-iframes`)], { encoding: 'utf8' });
    fs.rmSync(sinMapa, { force: true });
    process.stdout.write(`── ${id} (${p.sin_iframes.join(', ')} sin iframes): ${r2.status === 0 ? 'pasa' : 'FALLA'}
${(r2.stdout || '').trim()}
`);
    if (r2.status !== 0) ok = false;
  }
  if (!ok) fallos++;
  resumen.push(`| ${id} | ${ok ? 'pasa' : `falla (código ${r.status})`} | ${path.join(out, id, 'report.md')} |`);
  process.stdout.write(`── ${id}: ${ok ? 'pasa' : 'FALLA'}\n${(r.stdout || '').trim()}\n${(r.stderr || '').trim()}\n`);
}
const md = ['| Pantalla | Resultado | Informe |', '|---|---|---|', ...resumen].join('\n');
fs.writeFileSync(path.join(out, 'resumen.md'), md + '\n');
console.log('\n' + md);
process.exit(fallos ? 1 : 0);
