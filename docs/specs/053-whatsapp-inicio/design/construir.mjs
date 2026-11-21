// Construye candidatos de la 053 desde la página de inicio renderizada en local (base.html).
// Uso: node construir.mjs <base.html> <salida.html> <variante: base|c1|...>
import fs from 'node:fs';

const [, , entrada, salida, variante] = process.argv;
let h = fs.readFileSync(entrada, 'utf8');

// Recursos del sitio → rutas del repo servido en la raíz (/public/...).
h = h.replace(/http:\/\/localhost:8000\/(css|js|images)\/([^"?]+)(\?v=\d+)?/g, '/public/$1/$2');
// Íconos del navegador y enlaces de navegación: no se miden.
h = h.replace(/<link[^>]+rel="(icon|apple-touch-icon|shortcut icon)"[^>]*>\s*/g, '');
h = h.replace(/http:\/\/localhost:8000/g, '#');

const WA = 'https://wa.me/527530000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20agendar%20una%20cita.';
// Burbuja de conversación dibujada con trazo, como los demás íconos del sitio (no el logotipo de WhatsApp).
const burbuja = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 19.5l1.2-3.6A8 8 0 1 1 8.4 18.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 10.5h6M9 13.5h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
const boton = (extra) => `<a class="c-btn c-btn--secondary pub-wa${extra}" href="${WA}" target="_blank" rel="noopener noreferrer" aria-label="Escríbenos por WhatsApp (se abre WhatsApp en una pestaña nueva)">${burbuja}Escríbenos por WhatsApp</a>`;

const variantes = {
  base: { css: '', hero: (s) => s, visita: (s) => s },
  c1: {
    css: `
/* 053 · c1: «Escríbenos por WhatsApp» como botón secundario junto al teléfono (primera pantalla y contacto). */
.pub-hero__facts a.pub-wa { font-size: var(--text-publico-small); }
.pub-hero__facts a.pub-wa:hover { text-decoration: none; }
.pub-visit__acciones { display: flex; flex-wrap: wrap; gap: var(--space-4); }
`,
    // Primera pantalla: un elemento más en la lista del horario y el teléfono, justo después del teléfono.
    hero: (s) => s.replace(/(<li><a href="tel:\+527530000000">753 000 0000<\/a><\/li>)/, `$1\n                <li>${boton('')}</li>`),
    // Contacto: después del teléfono, el botón y «Cómo llegar» en una misma fila.
    visita: (s) => s.replace(/(<a class="pub-visit__tel"[^>]*>753 000 0000<\/a>\s*)<div>/, `$1<div class="pub-visit__acciones">\n                    ${boton('')}`),
  },
  c2: {
    css: `
/* 053 · c2 (corrige c1, evaluador): el teléfono y WhatsApp como un solo grupo de contacto en su propia fila,
   con el horario como dato aparte encima; en contacto, los dos botones forman un grupo que se reparte el ancho
   (iguales al apilarse); por debajo de 560 px, botones a todo el ancho como las acciones del hero. */
.pub-hero__facts > li.pub-hero__contacto { display: flex; flex-basis: 100%; flex-wrap: wrap; align-items: center; gap: var(--space-2) var(--space-6); }
.pub-hero__facts a.pub-wa { font-size: var(--text-publico-small); }
.pub-hero__facts a.pub-wa:hover { text-decoration: none; }
.pub-visit__acciones { display: flex; flex-wrap: wrap; gap: var(--space-4); margin-top: var(--space-4); }
.pub-visit__acciones .c-btn { flex: 1 1 auto; margin-top: 0; }
@media (max-width: 559px) {
    .pub-hero__contacto .pub-wa,
    .pub-visit__acciones .c-btn { width: 100%; padding: 0 var(--space-6); }
}
`,
    hero: (s) => s.replace(/<li>(<a href="tel:\+527530000000">753 000 0000<\/a>)<\/li>/, `<li class="pub-hero__contacto">$1${boton('')}</li>`),
    visita: (s) => s.replace(/(<a class="pub-visit__tel"[^>]*>753 000 0000<\/a>\s*)<div>/, `$1<div class="pub-visit__acciones">\n                    ${boton('')}`),
  },
};
const v = variantes[variante];
if (!v) { console.error('variante desconocida'); process.exit(2); }
const antes = h;
h = v.visita(v.hero(h));
if (variante !== 'base' && (h.match(/pub-wa/g) || []).length < 2) { console.error('no se insertaron los dos botones'); process.exit(1); }
if (v.css) h = h.replace('</head>', `<style id="cambios-053">${v.css}</style>\n</head>`);
h = h.replace('<head>', `<head>\n<!-- Candidato ${variante} de /design-spec 053: la página de inicio renderizada en local (main, a2cd958) con el CSS y el JS reales de public/; lo nuevo va en <style id="cambios-053">. Se mide servido por HTTP con la raíz en el repo. -->`);
fs.writeFileSync(salida, h);
console.log(salida, antes === h ? '(sin cambios)' : 'ok');
