// Capturas y medidas del sistema de diseño extraído (AI-DD 1.11.2, design.md §3).
//
// Uso:
//   node capturar.mjs                      todas las vistas y los recortes de componentes
//   node capturar.mjs <vista> [<vista>…]   solo esas vistas (por su id en VISTAS), sin recortes
//   node capturar.mjs --seco [<vista>…]    imprime lo que capturaría y termina (sin navegador ni base)
//
// Se ejecuta en un contenedor de Playwright conectado a la red de Docker de la app, contra una
// base de datos SOLO de prueba, `dentissa_capturas` (nunca la de desarrollo con datos reales).
// Procedimiento (el que se usó el 2026-10-04; desde la carpeta del repo, con `docker compose up -d`):
//
// 1. Base de prueba: el volcado ficticio y las migraciones.
//      docker compose exec -T db mariadb -uroot -p<root> -e "DROP DATABASE IF EXISTS dentissa_capturas;
//        CREATE DATABASE dentissa_capturas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
//      docker compose exec -T db mariadb -uroot -p<root> dentissa_capturas < database/dentissa.sql
//      docker compose exec -T -e DB_DATABASE=dentissa_capturas app php artisan migrate --force
//
// 2. Anonimizar (SQL generado en una carpeta temporal fuera del repo y aplicado con mariadb):
//    - cada usuario pasa a <rol><n>@dentissa.test, con nombre de prueba («Ana Administra 1»,
//      «Diana Dentista 1», «Carlos Paciente 1»…), teléfono 555 010 <n>, sin foto ni google_id;
//    - pacientes (dirección, ciudad, contacto y teléfono de emergencia, notas médicas, alergias),
//      cédula del personal, notas de las citas y diagnóstico y tratamiento de los expedientes:
//      textos «de prueba» donde no son nulos;
//    - admin1, dentista1 y cliente1 con la contraseña de CLAVE (password_hash con PASSWORD_BCRYPT);
//    - una asistente, asistente1@dentissa.test (misma contraseña), en `personal` y vinculada al
//      dentista en `asistente_dentista`.
//
// 3. Datos de prueba (con fechas relativas al día de la captura): citas del dentista con
//    cliente1–cliente3 en todos los estados (hoy agendada, confirmada y en curso; una futura;
//    cancelada, no asistió y completada), un expediente de la completada, el horario del dentista
//    de lunes a viernes y una persona administrativa (administrativo1@dentissa.test, sin
//    contraseña). Para las pantallas de reprogramar (spec 028):
//    - cita_reprogramar: una cita AGENDADA FUTURA del dentista (la abre el personal como admin);
//    - cita_reprogramar_cliente: una cita agendada de cliente1 que empiece dentro de 24 h O MÁS en
//      el momento de capturar (si no, la pantalla del paciente muestra el aviso de plazo).
//
// 4. /tmp/capturas/ids.json (carpeta temporal fuera del repo, montada en /tmp/capturas) con los
//    identificadores que usan las rutas: cita (de cliente1, hoy), cita_futura, cita_historial
//    (una cita con entradas en historial_citas), cita_reprogramar, cita_reprogramar_cliente,
//    paciente (cliente1), servicio, expediente, p_dentista, p_asistente, p_administrador y
//    usuario (cliente2).
//
// 5. Servidor de la app contra esa base, en :8001 (sin recargar `.env`, que volvería a la base de
//    desarrollo, y con la cookie de sesión válida por http):
//      docker compose run -d --rm --name dentissa_capturas_app -e DB_DATABASE=dentissa_capturas \
//        -e SESSION_SECURE_COOKIE=false -e SESSION_DOMAIN=null \
//        app php artisan serve --no-reload --host=0.0.0.0 --port=8001
//
// 6. Playwright 1.63.0 (imagen mcr.microsoft.com/playwright:v1.63.0-noble) con el paquete en un
//    volumen aparte:
//      docker run --rm -v dentissa_pw_node:/pw -w /pw mcr.microsoft.com/playwright:v1.63.0-noble \
//        sh -c "npm init -y && npm i --no-audit --no-fund --ignore-scripts playwright@1.63.0"
//
// 7. Captura, en la red de la app (dentissa_dentissa_network):
//      docker run --rm --network dentissa_dentissa_network -v dentissa_pw_node:/pw \
//        -v <repo>/docs/design:/design -v <carpeta temporal>:/tmp/capturas \
//        -e BASE=http://dentissa_capturas_app:8001 -e CLAVE=<contraseña de las cuentas de prueba> \
//        mcr.microsoft.com/playwright:v1.63.0-noble \
//        sh -c "cp /design/capturar.mjs /pw/ && node /pw/capturar.mjs [<vista>…]"
//    (Se copia a /pw porque los módulos ES resuelven `playwright` desde la carpeta del script.)
//    Con vistas elegidas, `git status docs/design/capturas/` solo debe mostrar esas capturas.
//
// 8. Limpieza: DROP DATABASE dentissa_capturas; docker rm -f dentissa_capturas_app;
//    docker volume rm dentissa_pw_node; y borrar la carpeta temporal (ids.json, medidas.json).
//
// Entradas: /tmp/capturas/ids.json y las cuentas <rol>1@dentissa.test con la contraseña de CLAVE.
// Salidas: /design/capturas/<vista>-<ancho>.png, /design/capturas/componentes/<componente>.png
// (solo sin vistas elegidas) y /tmp/capturas/medidas.json.
import fs from 'node:fs';

const ARGS = process.argv.slice(2);
const SECO = ARGS.includes('--seco');
const ELEGIDAS = ARGS.filter((a) => !a.startsWith('--'));

const BASE = process.env.BASE;
const CLAVE = process.env.CLAVE;
const OUT = '/design/capturas';
const ANCHOS = { 390: { width: 390, height: 844 }, 1440: { width: 1440, height: 900 } };

const u = (ruta) => BASE + ruta.replace(/\{(\w+)\}/g, (_, k) => IDS[k]);
const sinValidar = async (page) => page.evaluate(() => document.querySelectorAll('form').forEach((f) => { f.noValidate = true; }));

// Vista: [id, rol, ruta, acción opcional antes de capturar]
const VISTAS = [
  ['auth-login', null, '/login'],
  ['auth-login-error', null, '/login', async (p) => {
    await p.fill('input[name=email]', 'nadie@dentissa.test');
    await p.fill('input[name=password]', 'clave-incorrecta');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button[type=submit]')]);
  }],
  ['auth-registro-paso1', null, '/registro'],
  ['auth-registro-paso2', null, '/registro', async (p) => {
    // Datos de prueba en el paso 1 para que nextStep(2) pase su validación.
    await p.fill('#nombre', 'Clara'); await p.fill('#apellido', 'Paciente');
    await p.fill('#email', 'nuevo@dentissa.test');
    await p.fill('#password', 'prueba-1234'); await p.fill('#password_confirmation', 'prueba-1234');
    await p.click('.btn-next');
  }],
  ['auth-registro-error', null, '/registro', async (p) => {
    await sinValidar(p);
    await Promise.all([p.waitForNavigation(), p.evaluate(() => document.querySelector('form').submit())]);
  }],

  ['admin-dashboard', 'admin', '/admin'],
  ['servicios-index', 'admin', '/admin/servicios'],
  ['servicios-modal-eliminar', 'admin', '/admin/servicios', async (p) => { await p.locator('[onclick^="abrirModalEliminar"]').first().click(); }],
  ['servicios-create', 'admin', '/admin/servicios/create'],
  ['servicios-create-error', 'admin', '/admin/servicios/create', async (p) => {
    await sinValidar(p);
    await Promise.all([p.waitForNavigation(), p.evaluate(() => document.querySelector('form[action$="/admin/servicios"]').submit())]);
  }],
  ['servicios-edit', 'admin', '/admin/servicios/{servicio}/edit'],
  ['citas-index-admin', 'admin', '/admin/citas'],
  ['citas-create-admin', 'admin', '/admin/citas/crear'],
  ['citas-show-admin', 'admin', '/admin/citas/{cita}'],
  ['pacientes-index-admin', 'admin', '/admin/pacientes'],
  ['pacientes-create-admin', 'admin', '/admin/pacientes/crear'],
  ['pacientes-show-admin', 'admin', '/admin/pacientes/{paciente}'],
  ['pacientes-edit-admin', 'admin', '/admin/pacientes/{paciente}/edit'],
  ['expedientes-create-admin', 'admin', '/admin/expedientes/crear?paciente_id={paciente}'],
  ['expedientes-show-admin', 'admin', '/admin/expedientes/{expediente}'],
  ['personal-index', 'admin', '/admin/personal'],
  ['personal-create', 'admin', '/admin/personal/crear'],
  ['personal-create-asistente', 'admin', '/admin/personal/crear', async (p) => { await p.evaluate(() => window.selectTipo('asistente')); }],
  ['personal-show-dentista', 'admin', '/admin/personal/{p_dentista}'],
  ['personal-show-asistente', 'admin', '/admin/personal/{p_asistente}'],
  ['personal-show-administrador', 'admin', '/admin/personal/{p_administrador}'],
  ['personal-edit', 'admin', '/admin/personal/{p_dentista}/edit'],
  ['admin-reportes', 'admin', '/admin/reportes'],
  ['usuarios-index', 'admin', '/admin/usuarios'],
  ['usuarios-modal-estado', 'admin', '/admin/usuarios', async (p) => { await p.locator('[onclick^="abrirModalEstado"]').first().click(); }],
  ['usuarios-create', 'admin', '/admin/usuarios/crear'],
  ['usuarios-show', 'admin', '/admin/usuarios/{usuario}'],
  ['usuarios-edit', 'admin', '/admin/usuarios/{usuario}/editar'],

  ['dentista-dashboard', 'dentista', '/dentista'],
  ['citas-index-dentista', 'dentista', '/dentista/citas'],
  ['citas-create-dentista', 'dentista', '/dentista/citas/crear'],
  ['citas-show-dentista', 'dentista', '/dentista/citas/{cita}'],
  ['pacientes-index-dentista', 'dentista', '/dentista/pacientes'],
  ['pacientes-show-dentista', 'dentista', '/dentista/pacientes/{paciente}'],
  ['pacientes-edit-dentista', 'dentista', '/dentista/pacientes/{paciente}/edit'],
  ['expedientes-index-dentista', 'dentista', '/dentista/expedientes'],
  ['expedientes-create-dentista', 'dentista', '/dentista/expedientes/crear?paciente_id={paciente}'],
  ['expedientes-show-dentista', 'dentista', '/dentista/expedientes/{expediente}'],
  ['error-403', 'dentista', '/admin'],
  ['error-404', 'dentista', '/no-existe'],

  ['asistente-dashboard', 'asistente', '/asistente'],
  ['asistente-doctores', 'asistente', '/asistente/doctores'],
  ['citas-index-asistente', 'asistente', '/asistente/citas'],
  ['citas-create-asistente', 'asistente', '/asistente/citas/crear'],
  ['citas-show-asistente', 'asistente', '/asistente/citas/{cita}'],
  ['pacientes-index-asistente', 'asistente', '/asistente/pacientes'],
  ['pacientes-create-asistente', 'asistente', '/asistente/pacientes/crear'],
  ['pacientes-show-asistente', 'asistente', '/asistente/pacientes/{paciente}'],
  ['expedientes-show-asistente', 'asistente', '/asistente/expedientes/{expediente}'],

  ['cliente-dashboard', 'cliente', '/cliente'],
  ['citas-index-cliente', 'cliente', '/cliente/citas'],
  ['citas-index-cliente-pasadas', 'cliente', '/cliente/citas?filtro=pasadas'],
  ['citas-show-cliente', 'cliente', '/cliente/citas/{cita}'],
  ['cliente-agendar', 'cliente', '/cliente/agendar'],
  ['cliente-perfil', 'cliente', '/cliente/perfil'],

  // Spec 028: reprogramar una cita.
  ['citas-reprogramar-admin', 'admin', '/admin/citas/{cita_reprogramar}/reprogramar'],
  ['citas-reprogramar-cliente', 'cliente', '/cliente/citas/{cita_reprogramar_cliente}/reprogramar'],
];

// Recortes de componentes: [nombre, vista de origen, selector]
const COMPONENTES = [
  ['boton-primario', '/admin/pacientes', '.btn-primary'],
  ['boton-contorno', '/admin/citas/{cita}', '.btn-outline'],
  ['boton-pequeno', '/admin/pacientes', '.btn-sm'],
  ['campo', '/admin/citas/crear', '.field'],
  ['tabla', '/admin/citas', 'table'],
  ['tarjeta-resumen', '/admin', '.stat-card'],
  ['badges-estado', '/admin/citas', 'table tbody'],
  ['barra-lateral', '/admin', '.sidebar'],
  ['barra-superior', '/admin', '.topbar'],
  ['estado-vacio', '/admin/reportes', '.content'],
  ['historial', '/admin/citas/{cita_historial}', ':has(> .timeline-item)'],
  ['horario-dia', '/admin/personal/{p_dentista}', '.horario-dia'],
  ['lista-citas', '/admin/personal/{p_dentista}', '.lista-citas'],
];

const MEDIR = () => {
  const cs = (sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const s = getComputedStyle(el); const r = el.getBoundingClientRect();
    return {
      bg: s.backgroundColor, color: s.color, font: s.fontFamily, size: s.fontSize, weight: s.fontWeight,
      alto: Math.round(r.height), ancho: Math.round(r.width), padding: s.padding, radio: s.borderRadius,
      borde: `${s.borderTopWidth} ${s.borderTopStyle} ${s.borderTopColor}`, outline: s.outlineStyle,
    };
  };
  return {
    body: cs('body'), sidebar: cs('.sidebar'), navItem: cs('.nav-item'), navLabel: cs('.nav-label'),
    topbar: cs('.topbar'), titulo: cs('.topbar-title, h1'), content: cs('.content'), card: cs('.card'),
    btnPrimario: cs('.btn-primary'), btn: cs('.btn'), btnSm: cs('.btn-sm'), btnSubmit: cs('.btn-submit'),
    campo: cs('.field input, .field select, input.field, select.field, input[type=email]'),
    etiqueta: cs('.field-label, label'), th: cs('th'), td: cs('td'), badge: cs('.badge'),
    vacio: cs('.empty-state'), modal: cs('.modal-box, [id*=modal] > div'),
    desbordeHorizontal: document.documentElement.scrollWidth > window.innerWidth,
    anchoDocumento: document.documentElement.scrollWidth,
  };
};

// Con vistas elegidas, solo esas y sin recortes de componentes (el resto de la galería no cambia).
const desconocidas = ELEGIDAS.filter((id) => !VISTAS.some(([v]) => v === id));
if (desconocidas.length) throw new Error(`vistas desconocidas: ${desconocidas.join(', ')}`);
const vistas = ELEGIDAS.length ? VISTAS.filter(([id]) => ELEGIDAS.includes(id)) : VISTAS;
const componentes = ELEGIDAS.length ? [] : COMPONENTES;

if (SECO) {
  console.log(`vistas (${vistas.length}):`);
  for (const [id, rol, ruta] of vistas) console.log(`  ${id} [${rol ?? 'invitado'}] ${ruta}`);
  console.log(`componentes (${componentes.length}):`);
  for (const [nombre] of componentes) console.log(`  ${nombre}`);
  process.exit(0);
}

const { chromium } = await import('playwright');
const IDS = JSON.parse(fs.readFileSync('/tmp/capturas/ids.json', 'utf8'));
const browser = await chromium.launch();
const contextos = {};
async function contexto(rol) {
  if (contextos[rol]) return contextos[rol];
  const ctx = await browser.newContext({ locale: 'es-MX' });
  if (rol) {
    const p = await ctx.newPage();
    await p.goto(u('/login'));
    await p.fill('input[name=email]', `${rol}1@dentissa.test`);
    await p.fill('input[name=password]', CLAVE);
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button[type=submit]')]);
    if (p.url().includes('/login')) throw new Error(`no se pudo entrar como ${rol}`);
    await p.close();
  }
  contextos[rol] = ctx;
  return ctx;
}

fs.mkdirSync(`${OUT}/componentes`, { recursive: true });
const medidas = {}; const estado = {};
for (const [id, rol, ruta, accion] of vistas) {
  for (const ancho of Object.keys(ANCHOS)) {
    const clave = `${id}-${ancho}`;
    try {
      const ctx = await contexto(rol);
      const page = await ctx.newPage();
      await page.setViewportSize(ANCHOS[ancho]);
      const resp = await page.goto(u(ruta), { waitUntil: 'networkidle' });
      if (accion) { await accion(page); await page.waitForTimeout(400); }
      if (rol && page.url().includes('/login')) throw new Error('la sesión se perdió: la vista acabó en /login');
      if (resp && resp.status() >= 500) { await page.close(); throw new Error(`HTTP ${resp.status()}: no se guarda la captura (puede mostrar detalles internos)`); }
      await page.screenshot({ path: `${OUT}/${clave}.png`, fullPage: true, animations: 'disabled' });
      medidas[clave] = { http: resp ? resp.status() : null, url: page.url().replace(BASE, ''), ...(await page.evaluate(MEDIR)) };
      estado[clave] = 'capturada';
      await page.close();
    } catch (e) {
      estado[clave] = `sin captura (${String(e.message).split('\n')[0]})`;
    }
  }
}

for (const [nombre, ruta, sel] of componentes) {
  const admin = await contexto('admin');
  try {
    const page = await admin.newPage();
    await page.setViewportSize(ANCHOS[1440]);
    await page.goto(u(ruta), { waitUntil: 'networkidle' });
    if (page.url().includes('/login')) throw new Error('la sesión se perdió');
    await page.locator(sel).first().screenshot({ path: `${OUT}/componentes/${nombre}.png`, animations: 'disabled' });
    estado[`componente:${nombre}`] = 'capturada';
    await page.close();
  } catch (e) {
    estado[`componente:${nombre}`] = `sin captura (${String(e.message).split('\n')[0]})`;
  }
}

fs.writeFileSync('/tmp/capturas/medidas.json', JSON.stringify({ medidas, estado }, null, 1));
await browser.close();
console.log(Object.entries(estado).filter(([, v]) => v !== 'capturada').map(([k, v]) => `${k}: ${v}`).join('\n') || 'todo capturado');
