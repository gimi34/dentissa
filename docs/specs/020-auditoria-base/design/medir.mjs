#!/usr/bin/env node
// Mide un candidato de la 020 pantalla por pantalla, como el medir.mjs de la 041: parte
// design/criterios.json por pantalla (sus KPIs más los de "pantalla": "*", con los anchos y el
// text_scope propios de la pantalla) y llama a evaluate.mjs con cada una.
//
// Barra lateral del layout ("barra_lateral" en la pantalla), dos excepciones:
// - Deuda DS1 (decisión del usuario, 2026-10-28): en el escritorio, la barra (#C75078) no cumple el
//   contraste; es deuda del objetivo 6. Se admite G-contraste fallido a 900 px o más.
// - Menú cerrado del teléfono (spec 033): por debajo de 900 px el panel está oculto a propósito
//   (visibility: hidden) hasta que se abre con su botón; G-aparece y G-reducido lo cuentan como
//   contenido que nunca aparece. Se admiten esos dos fallos por debajo de 900 px. El menú abierto se
//   mide como pantalla propia (menu.html), sin excepción.
// Si lo único que falla son esas puertas a esos anchos, se repite la medición (sin KPIs) sobre una
// copia con la barra fuera de la medición (aria-hidden): si entonces pasan todas las puertas, los
// fallos eran solo de la barra y la pantalla pasa con la excepción anotada. Solo con carpetas
// locales; sobre una URL, la excepción se comprueba a mano.
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
const esUrl = /^https?:\/\//.test(base);
const ESCRITORIO = 900;
fs.mkdirSync(out, { recursive: true });

const correr = (target, crit, carpeta) => {
  const critPath = path.join(out, `criterios-${carpeta}.json`);
  fs.writeFileSync(critPath, JSON.stringify(crit, null, 2));
  return spawnSync(process.execPath, [evaluate, target, '--criteria', critPath, '--out', path.join(out, carpeta)], { encoding: 'utf8' });
};

let fallos = 0;
const resumen = [];
for (const [id, p] of Object.entries(pantallas)) {
  const widths = p.widths || C.widths;
  const crit = { ...comun, widths, ...(p.text_scope ? { text_scope: p.text_scope } : {}),
    kpis: kpis.filter(k => k.pantalla === id || k.pantalla === '*') };
  const target = esUrl ? `${base.replace(/\/$/, '')}/${p.url || p.file}` : path.join(base, p.file);
  const r = correr(target, crit, id);
  let ok = r.status === 0, nota = '';
  process.stdout.write(`── ${id}: ${ok ? 'pasa' : 'falla'}\n${(r.stdout || '').trim()}\n${(r.stderr || '').trim()}\n`);
  if (!ok && r.status === 1 && p.barra_lateral && !esUrl) {
    const rep = JSON.parse(fs.readFileSync(path.join(out, id, 'report.json'), 'utf8'));
    const malas = rep.gates.filter(g => !g.pass);
    const ancho = d => Number((d.match(/^(\d+)px/) || [])[1]);
    const admitida = g => (g.id === 'G-contraste' && g.details.every(d => ancho(d) >= ESCRITORIO)) ||
      (['G-aparece', 'G-reducido'].includes(g.id) && g.details.every(d => ancho(d) < ESCRITORIO));
    const soloBarra = rep.kpis.every(k => k.pass) && malas.every(admitida);
    if (soloBarra) {
      const copia = path.join(base, `.sin-barra-${p.file}`);
      fs.writeFileSync(copia, fs.readFileSync(target, 'utf8').replace('<aside class="sidebar"', '<aside aria-hidden="true" class="sidebar"'));
      const r2 = correr(copia, { ...crit, kpis: [] }, `${id}-sin-barra`);
      fs.rmSync(copia);
      const rep2 = JSON.parse(fs.readFileSync(path.join(out, `${id}-sin-barra`, 'report.json'), 'utf8'));
      const otras = rep2.gates.filter(g => !g.pass && malas.some(m => m.id === g.id));
      if (r2.status === 0) {
        ok = true;
        nota = ` (solo en la barra lateral: ${malas.map(g => g.id === 'G-contraste' ? 'G-contraste en el escritorio, deuda DS1' : `${g.id} con el menú cerrado del teléfono`).join('; ')})`;
      } else {
        nota = ` (fuera de la barra lateral también fallan: ${otras.map(g => g.id).join(', ') || 'otras puertas'})`;
      }
      process.stdout.write(`   comprobación sin la barra: ${ok ? 'el resto pasa' : 'falla'} · ${(r2.stdout || '').trim()}\n`);
    }
  }
  if (!ok) fallos++;
  resumen.push(`| ${id} | ${ok ? 'pasa' : `falla (código ${r.status})`}${nota} | ${path.join(out, id, 'report.md')} |`);
}
const md = ['| Pantalla | Resultado | Informe |', '|---|---|---|', ...resumen].join('\n');
fs.writeFileSync(path.join(out, 'resumen.md'), md + '\n');
console.log('\n' + md);
process.exit(fallos ? 1 : 0);
