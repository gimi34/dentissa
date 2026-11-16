#!/usr/bin/env node
// Spec 048 (T024; plan → Estrategia de pruebas): mide en el navegador los cuatro paneles de inicio con
// citas. No sustituye a la CI; se ejecuta en /implement y en /review.
//
// Comprueba, por panel y ancho (320, 390 y 1440 px en Chromium; 1440 px también en WebKit y Firefox):
//   - CA1–CA4: pulsar otra parte de la cita (la celda «Servicio»; en la dentista, el nombre) abre su detalle;
//   - K1: por debajo de 900 px, cada cita mide ≥ 44 px de alto;
//   - K4/CA9: sin desplazamiento lateral (scrollWidth ≤ ancho de la ventana);
//   - CA5/K2: con Tab, cada cita es una sola parada e Intro la abre; el enlace no dibuja anillo propio y el ::after sí,
//     con contraste ≥ 3:1 sobre el fondo de la cita;
//   - A2: en reposo, la hora o fecha tiene el color de su celda y sin subrayado;
//   - CA6/A16: en el escritorio, la agenda de la dentista cambia de fondo al pasar el puntero;
//   - RNF: sin JavaScript, pulsar la cita la abre igual (solo Chromium a 390 px).
// Deja una captura con el foco en la primera cita de cada panel a 390 y 1440 px.
//
// Preparación (ver la nota de T024 en tasks.md):
//   docker exec dentissa_app php .ai/inspect/048-cuentas.php crear     # cuentas @medicion-048.test
//   docker exec -d -e SESSION_DOMAIN=null dentissa_app php artisan serve --no-reload --host=0.0.0.0 --port=8048
//   docker run --rm --network dentissa_dentissa_network -v dentissa_pw_node:/pw \
//     -v <repo>/docs/specs/048-citas-panel-detalle/design:/m -v <carpeta temporal>:/tmp/048 \
//     -e BASE=http://dentissa_app:8048 -e CLAVE=<contraseña> mcr.microsoft.com/playwright:v1.63.0-noble \
//     sh -c "cp /m/medir.mjs /pw/medir048.mjs && node /pw/medir048.mjs"
// Al terminar: parar el servidor y `048-cuentas.php borrar`.
// Código 0: todo pasa · 1: algún fallo.
import { chromium, webkit, firefox } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://dentissa_app:8048';
const CLAVE = process.env.CLAVE;
const OUT = process.env.OUT || '/tmp/048';
if (!CLAVE) { console.log('Falta CLAVE'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

const PANELES = [
  { rol: 'admin', url: '/admin', otra: 'td[data-label="Servicio"]' },
  { rol: 'asistente', url: '/asistente', otra: 'td[data-label="Servicio"]' },
  { rol: 'dentista', url: '/dentista', otra: '.timeline-title' },
  { rol: 'cliente', url: '/cliente', otra: 'td[data-label="Servicio"]' },
];
const PASADAS = [
  ['chromium', chromium, [320, 390, 1440]],
  ['webkit', webkit, [1440]],
  ['firefox', firefox, [1440]],
];

let fallos = 0;
const informe = [];
const ok = (cond, texto) => { if (!cond) fallos++; informe.push(`${cond ? 'OK   ' : 'FALLO'} ${texto}`); };

function luminancia([r, g, b]) {
  const l = [r, g, b].map(c => { c /= 255; return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4; });
  return 0.2126 * l[0] + 0.7152 * l[1] + 0.0722 * l[2];
}
const rgb = s => (s.match(/\d+(\.\d+)?/g) || []).slice(0, 3).map(Number);
// Pulsa en el centro de esa parte de la cita, como una persona: lo que recibe el clic es el ::after del enlace.
async function pulsar(pagina, parte) {
  await parte.scrollIntoViewIfNeeded();
  const b = await parte.boundingBox();
  await pagina.mouse.click(b.x + b.width / 2, b.y + b.height / 2);
}
const contraste = (a, b) => { const [x, y] = [luminancia(rgb(a)), luminancia(rgb(b))].sort((m, n) => n - m); return (x + 0.05) / (y + 0.05); };

// Una sesión por rol y navegador; los contextos siguientes la reutilizan (también el de sin JavaScript).
const sesiones = new Map();
async function entrar(navegador, nombre, rol, opciones) {
  const clave = `${nombre}:${rol}`;
  if (!sesiones.has(clave)) {
    const c = await navegador.newContext();
    const p = await c.newPage();
    await p.goto(`${BASE}/login`);
    await p.fill('#email', `${rol}@medicion-048.test`);
    await p.fill('#password', CLAVE);
    await Promise.all([p.waitForURL(u => !u.pathname.startsWith('/login')), p.click('button[type="submit"]')]);
    sesiones.set(clave, await c.storageState());
    await c.close();
  }
  const contexto = await navegador.newContext({ ...opciones, storageState: sesiones.get(clave) });
  return [contexto, await contexto.newPage()];
}

for (const [nombre, motor, anchos] of PASADAS) {
  const navegador = await motor.launch();
  for (const ancho of anchos) {
    for (const panel of PANELES) {
      const etiqueta = `${nombre} ${ancho}px ${panel.url}`;
      const [contexto, pagina] = await entrar(navegador, nombre, panel.rol, { viewport: { width: ancho, height: 900 } });
      await pagina.goto(`${BASE}${panel.url}`);
      const citas = pagina.locator('.fila-cita');
      const n = await citas.count();
      ok(n > 0, `${etiqueta}: hay ${n} citas con .fila-cita`);
      if (n === 0) { await contexto.close(); continue; }

      // Sin desplazamiento lateral.
      const lateral = await pagina.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      ok(lateral <= 0, `${etiqueta}: sin desplazamiento lateral (${lateral} px de más)`);

      for (let i = 0; i < n; i++) {
        const cita = citas.nth(i);
        await cita.scrollIntoViewIfNeeded();
        const caja = await cita.boundingBox();
        if (ancho < 900) ok(caja.height >= 44, `${etiqueta}: cita ${i + 1} mide ${Math.round(caja.height)} px de alto (≥ 44)`);

        // Reposo (A2): el enlace de texto con el color de su celda y sin subrayado.
        const reposo = await cita.locator('.cita-enlace').evaluate(a => {
          const s = getComputedStyle(a); const p = getComputedStyle(a.parentElement);
          return { btn: a.classList.contains('btn'), color: s.color, padre: p.color, deco: s.textDecorationLine };
        });
        if (!reposo.btn) ok(reposo.color === reposo.padre && reposo.deco === 'none', `${etiqueta}: cita ${i + 1} en reposo como su celda (${reposo.color}, ${reposo.deco})`);
      }

      // Pulsar otra parte de la primera cita abre su detalle.
      const href = await citas.first().locator('.cita-enlace').getAttribute('href');
      await Promise.all([pagina.waitForURL(href), pulsar(pagina, citas.first().locator(panel.otra).first())]).catch(() => {});
      ok(pagina.url() === href, `${etiqueta}: pulsar «${panel.otra}» abre ${new URL(href).pathname}`);
      await pagina.goto(`${BASE}${panel.url}`);

      // Puntero en el escritorio: la agenda de la dentista cambia de fondo (A16).
      if (panel.rol === 'dentista' && ancho >= 900) {
        await pagina.mouse.move(0, 0);   // el ratón puede seguir sobre la cita tras el clic anterior
        const antes = await citas.first().evaluate(e => getComputedStyle(e).backgroundColor);
        await citas.first().hover();
        const despues = await citas.first().evaluate(e => getComputedStyle(e).backgroundColor);
        ok(antes !== despues, `${etiqueta}: al pasar el puntero cambia el fondo (${antes} → ${despues})`);
        await pagina.mouse.move(0, 0);
      }

      // Tab: cada cita es una sola parada; un solo anillo con contraste ≥ 3:1.
      await pagina.evaluate(() => { document.activeElement?.blur(); window.scrollTo(0, 0); });
      const paradas = [];
      for (let k = 0; k < 120; k++) {
        await pagina.keyboard.press('Tab');
        const id = await pagina.evaluate(() => {
          const a = document.activeElement;
          return a && a.classList.contains('cita-enlace') ? a.getAttribute('href') : null;
        });
        if (id) paradas.push(id);
        if (paradas.length > 0 && !id && paradas.length >= n) break;
      }
      const hrefs = await citas.locator('.cita-enlace').evaluateAll(as => as.map(a => a.getAttribute('href')));
      ok(hrefs.every(h => paradas.filter(p => p === h).length === 1), `${etiqueta}: una parada de Tab por cita (${paradas.length} de ${n})`);

      await citas.first().locator('.cita-enlace').focus();
      const foco = await citas.first().locator('.cita-enlace').evaluate(a => {
        const s = getComputedStyle(a); const d = getComputedStyle(a, '::after');
        const fila = a.closest('.fila-cita');
        let fondo = getComputedStyle(a.closest('td') || fila).backgroundColor;
        if (fondo === 'rgba(0, 0, 0, 0)' || fondo === 'transparent') fondo = getComputedStyle(fila).backgroundColor;
        if (fondo === 'rgba(0, 0, 0, 0)' || fondo === 'transparent') fondo = 'rgb(255, 255, 255)';
        return { propio: s.outlineStyle, anillo: d.outlineStyle, color: d.outlineColor, fondo, radio: d.borderTopLeftRadius };
      });
      const c = contraste(foco.color, foco.fondo);
      ok(foco.propio === 'none' && foco.anillo === 'solid', `${etiqueta}: un solo anillo (enlace ${foco.propio}, ::after ${foco.anillo})`);
      ok(c >= 3, `${etiqueta}: anillo ${foco.color} sobre ${foco.fondo}, ${c.toFixed(2)}:1 (≥ 3)`);
      if (ancho < 900 && panel.rol !== 'dentista') ok(foco.radio !== '0px', `${etiqueta}: el anillo sigue el radio de la tarjeta (${foco.radio})`);
      // /review R4: la agenda de la dentista no es tarjeta; su anillo no lleva radio.
      if (ancho < 900 && panel.rol === 'dentista') ok(foco.radio === '0px', `${etiqueta}: el anillo de la agenda va sin radio (${foco.radio})`);
      if (nombre === 'chromium' && ancho !== 320) await citas.first().screenshot({ path: `${OUT}/${panel.rol}-${ancho}-foco.png` });

      // /review R6 (CA5): con el foco en la cita, Intro la abre.
      const destino = await citas.first().locator('.cita-enlace').getAttribute('href');
      await Promise.all([pagina.waitForURL(destino), pagina.keyboard.press('Enter')]).catch(() => {});
      ok(pagina.url() === destino, `${etiqueta}: Intro abre ${new URL(destino).pathname}`);

      await contexto.close();
    }
  }

  // Sin JavaScript (Chromium a 390 px).
  if (nombre === 'chromium') {
    for (const panel of PANELES) {
      const [contexto, pagina] = await entrar(navegador, nombre, panel.rol, { viewport: { width: 390, height: 900 }, javaScriptEnabled: false });
      await pagina.goto(`${BASE}${panel.url}`);
      const cita = pagina.locator('.fila-cita').first();
      const href = await cita.locator('.cita-enlace').getAttribute('href');
      await Promise.all([pagina.waitForURL(href), pulsar(pagina, cita.locator(panel.otra).first())]).catch(() => {});
      ok(pagina.url() === href, `sin JS 390px ${panel.url}: pulsar la cita abre su detalle`);
      await contexto.close();
    }
  }
  await navegador.close();
}

fs.writeFileSync(`${OUT}/informe.txt`, informe.join('\n') + '\n');
console.log(informe.join('\n'));
console.log(fallos ? `\n${fallos} fallo(s)` : '\nTodo pasa');
process.exit(fallos ? 1 : 0);
