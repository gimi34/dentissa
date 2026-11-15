// Servidor estático mínimo para medir los candidatos de la 045 por HTTP (la máscara CSS del logo no carga desde file://).
// Uso, dentro de la imagen de Playwright con el repo en /repo: node docs/specs/045-terminos-privacidad/design/servir.mjs /repo 8099 &
import http from 'node:http'; import fs from 'node:fs'; import path from 'node:path';
const raiz = process.argv[2] || '/repo', puerto = +(process.argv[3] || 8099);
const tipos = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.svg': 'image/svg+xml', '.woff2': 'font/woff2', '.webp': 'image/webp', '.jpg': 'image/jpeg' };
http.createServer((q, r) => { const f = path.join(raiz, decodeURIComponent(q.url.split('?')[0])); fs.readFile(f, (e, d) => { if (e) { r.writeHead(404); r.end(); return; } r.writeHead(200, { 'content-type': tipos[path.extname(f)] || 'application/octet-stream' }); r.end(d); }); }).listen(puerto);
