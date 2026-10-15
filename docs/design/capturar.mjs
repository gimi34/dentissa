// Capturas y medidas del sistema de diseño extraído (AI-DD 1.11.2, design.md §3).
//
// Uso:
//   node capturar.mjs                      todas las vistas y los recortes de componentes
//   node capturar.mjs <vista> [<vista>…]   solo esas vistas (por su id en VISTAS), sin recortes
//   node capturar.mjs --seco [<vista>…]    imprime lo que capturaría y termina (sin navegador ni base)
//   node capturar.mjs --anchos=1024,1440 <vista>…   esos anchos en lugar de los de cada vista
//   node capturar.mjs --medir               test en el navegador de las specs 033 y 034 (anchos, toque,
//                                           menú, recortes, solapes, tarjetas, filtro, palabras largas,
//                                           sin JS y escritorio frente a la línea base); sale con 1 si algo falla
//   node capturar.mjs --seco --medir       lista las comprobaciones de --medir y termina
//   OUT=/tmp/capturas/base node capturar.mjs --linea-base   capturas a 1024 y 1440 de las pantallas que
//                                           --medir compara con la línea base (spec 034, D13)
//   OUT=/tmp/capturas/otra node capturar.mjs …   guarda las capturas en otra carpeta
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
//      dentista en `asistente_dentista`;
//    - spec 033 (textos largos, CA6): cliente1 pasa a «Maximiliana Guadalupe de los Ángeles
//      Fernández-Villaseñor de la Concepción», con un correo largo sin espacios
//      (cliente1.maximiliana.guadalupe.fernandezvillasenor@dentissa.test), que se pasa en
//      CORREO_CLIENTE para entrar con esa cuenta.
//
// 3. Datos de prueba (con fechas relativas al día de la captura): citas del dentista con
//    cliente1–cliente3 en todos los estados (hoy agendada, confirmada y en curso; una futura;
//    cancelada, no asistió y completada), un expediente de la completada, el horario del dentista
//    de lunes a viernes y una persona administrativa (administrativo1@dentissa.test, sin
//    contraseña). Para las pantallas de reprogramar (spec 028):
//    - cita_reprogramar: una cita AGENDADA FUTURA del dentista (la abre el personal como admin);
//    - cita_reprogramar_cliente: una cita agendada de cliente1 que empiece dentro de 24 h O MÁS en
//      el momento de capturar (si no, la pantalla del paciente muestra el aviso de plazo).
//    Spec 033: un servicio «Limpieza dental profunda con aplicación de flúor y sellado de fisuras»
//    en las citas de cliente1 (la próxima, una pasada y la de reprogramar).
//    Spec 034: cita_historial_largo, una cita agendada futura de cliente1 con 10 entradas en
//    historial_citas (el detalle con historial largo de D7). Las palabras de 30 letras de --medir no
//    tocan la base: se escriben en la página abierta, sobre los nombres de paciente, dentista,
//    servicio y usuario, y desaparecen al cerrarla.
//
// 4. /tmp/capturas/ids.json (carpeta temporal fuera del repo, montada en /tmp/capturas) con los
//    identificadores que usan las rutas: cita (de cliente1, hoy), cita_futura, cita_historial
//    (una cita con entradas en historial_citas), cita_historial_largo, cita_reprogramar, cita_reprogramar_cliente,
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
//        -e CORREO_CLIENTE=<correo largo de cliente1> \
//        mcr.microsoft.com/playwright:v1.63.0-noble \
//        sh -c "cp /design/capturar.mjs /pw/ && node /pw/capturar.mjs [<vista>…]"
//    (Se copia a /pw porque los módulos ES resuelven `playwright` desde la carpeta del script.)
//    Con vistas elegidas, `git status docs/design/capturas/` solo debe mostrar esas capturas.
//
// 7b. Línea base del escritorio (spec 034, D13; A40, A61, A66), justo antes de cada --medir, en la misma
//    sesión y con la misma base. Se captura con el código de producción, el tag v0.7.0 (el `main` local
//    puede estar desfasado), servido aparte:
//      git worktree add <carpeta temporal fuera del repo>/v070 v0.7.0
//      cp .env <carpeta temporal>/v070/.env
//      docker run -d --rm --name dentissa_base_app --network dentissa_dentissa_network \
//        -v <carpeta temporal>/v070:/var/www -v dentissa_vendor_data:/var/www/vendor -w /var/www \
//        -e DB_DATABASE=dentissa_capturas -e SESSION_SECURE_COOKIE=false -e SESSION_DOMAIN=null \
//        dentissa-app php artisan serve --no-reload --host=0.0.0.0 --port=8002
//    (`docker run` y no `docker compose run`: el volumen `.:/var/www` de Compose taparía la copia. La
//    imagen arranca php-fpm sin HTTP, de ahí `php artisan serve`. La 034 no cambia dependencias, así que
//    sirve el volumen `dentissa_vendor_data`.) Después, el paso 7 con `-e BASE=http://dentissa_base_app:8002`,
//    `-e OUT=/tmp/capturas/base` y `--linea-base`; luego --medir con `-e LINEA_BASE=/tmp/capturas/base`.
//    La línea base muestra el menú de la 033 con sesión iniciada en los cuatro roles. Si una cita cruza
//    el plazo de 24 h de «Reprogramar» y «Cancelar» entre las dos capturas, se repiten las dos.
//    Al terminar: docker rm -f dentissa_base_app; rm <carpeta temporal>/v070/.env;
//    git worktree remove <carpeta temporal>/v070.
//
// 8. Limpieza: DROP DATABASE dentissa_capturas; docker rm -f dentissa_capturas_app dentissa_base_app;
//    docker volume rm dentissa_pw_node; git worktree remove de la copia de v0.7.0 y su .env; y borrar
//    la carpeta temporal (ids.json, medidas.json, medir.json y la línea base).
//
// Entradas: /tmp/capturas/ids.json y las cuentas <rol>1@dentissa.test (cliente1, con
// CORREO_CLIENTE) con la contraseña de CLAVE.
// Salidas: /design/capturas/<vista>-<ancho>.png (o en OUT), /design/capturas/componentes/<componente>.png
// (solo sin vistas elegidas) y /tmp/capturas/medidas.json; con --medir, /tmp/capturas/medir.json; con
// --linea-base, OUT/<pantalla>-<ancho>.png.
import fs from 'node:fs';

const ARGS = process.argv.slice(2);
const SECO = ARGS.includes('--seco');
const MEDIR_MODO = ARGS.includes('--medir');
const BASE_MODO = ARGS.includes('--linea-base');
const LINEA_BASE = process.env.LINEA_BASE;
// Spec 034: palabra de 30 letras que --medir escribe en la página sobre los nombres (D8, A11).
const LARGA = 'Esternocleidomastoideosuperior';
const ANCHOS_ARG = ARGS.find((a) => a.startsWith('--anchos='))?.slice('--anchos='.length).split(',');
const ELEGIDAS = ARGS.filter((a) => !a.startsWith('--'));

const BASE = process.env.BASE;
const CLAVE = process.env.CLAVE;
const CORREO_CLIENTE = process.env.CORREO_CLIENTE;
const OUT = process.env.OUT ?? '/design/capturas';
const ANCHOS = {
  320: { width: 320, height: 700 }, 390: { width: 390, height: 844 }, '844x390': { width: 844, height: 390 },
  900: { width: 900, height: 800 }, 1024: { width: 1024, height: 768 }, 1440: { width: 1440, height: 900 },
};
// Spec 033: las 7 vistas del paciente se capturan también a 320; el menú abierto, solo en el móvil.
const VISTAS_PACIENTE = ['cliente-dashboard', 'citas-index-cliente', 'citas-index-cliente-pasadas', 'citas-show-cliente',
  'citas-reprogramar-cliente', 'cliente-agendar', 'cliente-perfil'];
const anchosDe = (id) => ANCHOS_ARG ?? (id.startsWith('menu-abierto-') ? ['390']
  : VISTAS_PACIENTE.includes(id) ? ['320', '390', '1440'] : ['390', '1440']);
const abrirMenu = async (p) => {
  await p.locator('.menu-btn').click({ timeout: 2000 });
  await p.waitForFunction(() => document.querySelector('.menu-btn')?.getAttribute('aria-expanded') === 'true');
};

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

  // Spec 033: el menú del móvil abierto, uno por rol.
  ['menu-abierto-admin', 'admin', '/admin', abrirMenu],
  ['menu-abierto-dentista', 'dentista', '/dentista', abrirMenu],
  ['menu-abierto-asistente', 'asistente', '/asistente', abrirMenu],
  ['menu-abierto-cliente', 'cliente', '/cliente', abrirMenu],
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

// ── Spec 033: test en el navegador (--medir) ─────────────────────────────────────────────
// Comprueba lo que PHPUnit no puede ver: anchos, cajas de toque, recortes, etiquetas y errores de
// los formularios, el menú del móvil de cada rol (foco, Escape, «Cerrar menú», velo, inert), la
// pasada sin JavaScript y el escritorio. Cada fallo es una línea «vista@ancho: qué».
const COMPROBACIONES = [
  'anchos: las 7 vistas del paciente a 320, 390, 844x390, 900, 1024 y 1440 sin desplazamiento horizontal, y ninguna .table-wrap más ancha que su contenedor (CA1, CA6, CA7)',
  'recortes: a 320 y 390, ningún control ni texto visible de .main sale de la pantalla ni de un antecesor que recorta; con control positivo (CA1)',
  'toque: a 320, 390 y 844x390, controles visibles de .main y, a 390, del menú abierto ≥ 44 × 44 (sin enlaces dentro de un <p>; la casilla, por su <label>) (033 CA4, 034 CA8, A44)',
  'casilla: a 390, en reprogramar, la etiqueta ≥ 44 px de alto y centrada (±2 px) con la casilla (A41)',
  'formularios: a 320, en agendar, perfil y reprogramar, cada etiqueta a ≤ 16 px de su campo; enviados vacíos, cada error dentro del ancho y junto a su campo (CA5)',
  'menú: a 320 y 390, para los 4 roles, botón visible y panel oculto; al abrir, aria-expanded, foco en la sección actual, <main> inerte, secciones, nombre y «Cerrar sesión» visibles; Escape, «Cerrar menú» (≥ 44 × 44, con nombre) y el velo cierran y devuelven el foco (CA2, CA3)',
  'horizontal: a 844x390, con el menú abierto se alcanzan el último enlace y «Cerrar sesión» (CA7)',
  'sin JS: a 390, el panel del paciente sin desplazamiento horizontal, «Menú» y «Cerrar menú» ocultos, la marca visible y el menú encima del contenido sin taparlo (A3, A40)',
  'escritorio: a 1440, para los 4 roles, botón, velo y marca ocultos y la barra lateral visible (CA8)',
  '034 pantallas del personal: paneles, citas, detalle y pacientes de admin, dentista y asistente, y servicios de admin, a 320, 390, 844x390 y 1440 sin desplazamiento horizontal; a 320 y 390, sin recortes (CA8)',
  '034 solapes: a 320, 390 y 844x390, en las pantallas incluidas, ningún control visible se cruza con otro ni sale de su tarjeta; con control positivo (CA8)',
  '034 tarjetas: a 320, 390 y 844x390, toda tabla visible es tabla-tarjetas; cada celda de valor muestra su etiqueta a la izquierda, en la línea de su primera línea, también con un valor de dos líneas; el estado, arriba a la derecha junto a la cabecera; y cada tarjeta, con un fondo distinto del de su contenedor, también la de hoy (CA1–CA4, A37, A39, A57)',
  '034 palabras largas: a 320 y 390, con una palabra de 30 letras en los nombres de paciente, dentista, servicio y usuario (banner y menú), ninguna pantalla incluida ni de las 7 del paciente se desplaza de lado ni recorta texto, y ninguna palabra que cabe en el ancho de su diseño (tarjeta o .card, menos la etiqueta) queda partida; con control positivo (CA7, A34, A38)',
  '034 filtro: a 390, plegado al cargar, «Filtros» lo despliega y cambia aria-expanded; a 320 y 390, desplegado, buscador al ancho del formulario y orden buscador < atajos < panel, atajos en una línea, sin solapes ni campos fuera de su tarjeta; sin JS, desplegado y sin «Filtros»; foco visible en los campos, «Hoy», «Filtros» y «Filtrar» (CA5, A33, A45, A55, A68, A69)',
  '034 foco: a 390, foco visible en un botón de tarjeta y en el enlace de la dentista (A60)',
  '034 detalle: a 320, 390 y 844x390, los bloques del detalle en una columna, en el orden de D7, a todo el ancho (CA6)',
  '034 panel de la dentista: a 320, 390 y 844x390, el estado y el enlace bajo el nombre en cada elemento de la línea de tiempo (CA2, A35)',
  '034 menú: a 390, «Menú» sin texto visible, con nombre y 44 × 44; con el menú abierto, el logo de --logo-max-w con fondo --p600 (CA9, CA10)',
  '034 cambios de modo (033 T064 y R21): (a) foco en «Cerrar menú» y paso a 1000 px → enlace actual; (b) foco en un enlace del menú y paso a 390 → «Menú»; (c) Intro y Escape → cerrado y foco en «Menú»; (d) «Atrás» → cerrado y sin inert; (R21) a 1000, foco en un enlace de la barra, clic en una zona sin foco y paso a 390 → el foco no se mueve (CA9, A10)',
  '034 sin JS: a 390, las pantallas del personal sin desplazamiento horizontal (CA8)',
  '034 escritorio: a 1024 y 1440, las pantallas incluidas del personal y del paciente y el detalle con historial largo de los tres roles, iguales a la línea base de LINEA_BASE (≤ 0,1 % de píxeles distintos, ninguno con diferencia > 2/255, sin los campos del filtro) (CA11, D13)',
];

const PANELES = { admin: '/admin', dentista: '/dentista', asistente: '/asistente', cliente: '/cliente' };
const RUTAS_PACIENTE = Object.fromEntries(VISTAS.filter(([id]) => VISTAS_PACIENTE.includes(id)).map(([id, , ruta]) => [id, ruta]));
// Spec 034: pantallas incluidas del personal, [id, rol, ruta].
const PANTALLAS_PERSONAL = ['admin', 'dentista', 'asistente'].flatMap((rol) => [
  [`${rol}-panel`, rol, `/${rol}`], [`${rol}-citas`, rol, `/${rol}/citas`], [`${rol}-detalle`, rol, `/${rol}/citas/{cita}`],
  [`${rol}-pacientes`, rol, `/${rol}/pacientes`],
]).concat([['admin-servicios', 'admin', '/admin/servicios']]);
// Las que --medir compara con la línea base a 1024 y 1440 (D13).
const COMPARADAS = [
  ...PANTALLAS_PERSONAL,
  ...['admin', 'dentista', 'asistente'].map((rol) => [`${rol}-detalle-historial`, rol, `/${rol}/citas/{cita_historial_largo}`]),
  ...Object.entries(RUTAS_PACIENTE).map(([id, ruta]) => [id, 'cliente', ruta]),
];

// Funciones que se evalúan en la página.
const EN_PAGINA = {
  anchos: () => ({
    doc: document.documentElement.scrollWidth, vista: window.innerWidth,
    tablas: [...document.querySelectorAll('.table-wrap')].filter((t) => {
      const r = t.getBoundingClientRect();
      return r.width > t.parentElement.clientWidth + 1 || r.right > window.innerWidth + 1;
    }).length,
  }),
  recortes: () => {
    const W = window.innerWidth; const fuera = [];
    const visible = (el) => el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden';
    const conTexto = (el) => [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
    const candidatos = [...document.querySelectorAll('.main *')].filter((el) =>
      el.matches('a, button, input:not([type=hidden]), select, textarea, label') || conTexto(el));
    for (const el of candidatos) {
      if (!visible(el)) continue;
      const r = el.getBoundingClientRect();
      if (!r.width || !r.height) continue;
      let anc = el.parentElement; let enScroll = false; let recortado = r.left < -1 || r.right > W + 1;
      while (anc && anc !== document.body) {
        const cs = getComputedStyle(anc);
        if (['auto', 'scroll'].includes(cs.overflowX)) { enScroll = true; break; }
        if (['hidden', 'clip'].includes(cs.overflowX) || ['hidden', 'clip'].includes(cs.overflowY)) {
          const a = anc.getBoundingClientRect();
          if (r.left < a.left - 1 || r.right > a.right + 1 || r.top < a.top - 1 || r.bottom > a.bottom + 1) recortado = true;
        }
        anc = anc.parentElement;
      }
      if (recortado && !enScroll) fuera.push(`${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}.${[...el.classList].join('.')} «${(el.textContent || el.value || '').trim().slice(0, 30)}»`);
    }
    return fuera;
  },
  toque: (selector) => {
    const pequenos = [];
    const visible = (el) => el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden';
    for (const el of document.querySelectorAll(selector)) {
      if (!visible(el) || el.closest('p')) continue;
      let caja = el;
      if (el.matches('input[type=checkbox], input[type=radio]')) caja = (el.id && document.querySelector(`label[for="${el.id}"]`)) || el.closest('label') || el;
      const r = caja.getBoundingClientRect();
      if (r.width < 43.5 || r.height < 43.5) pequenos.push(`${caja.tagName.toLowerCase()}.${[...caja.classList].join('.')} «${(caja.textContent || caja.getAttribute('aria-label') || caja.name || '').trim().slice(0, 25)}» ${Math.round(r.width)}×${Math.round(r.height)}`);
    }
    return pequenos;
  },
  casilla: () => {
    const caja = document.querySelector('.campo-casilla input[type=checkbox]');
    if (!caja) return ['no hay casilla en la vista'];
    const etiqueta = document.querySelector(`label[for="${caja.id}"]`);
    const rc = caja.getBoundingClientRect(); const re = etiqueta.getBoundingClientRect();
    const rango = document.createRange(); rango.selectNodeContents(etiqueta); const rt = rango.getBoundingClientRect();
    const fallos = [];
    if (re.height < 43.5) fallos.push(`la etiqueta mide ${Math.round(re.height)} px de alto`);
    if (Math.abs((rt.top + rt.bottom) / 2 - (rc.top + rc.bottom) / 2) > 2) fallos.push('el texto de la etiqueta no queda centrado con la casilla');
    return fallos;
  },
  etiquetas: () => {
    const fallos = [];
    for (const label of document.querySelectorAll('.main form label')) {
      const campo = (label.htmlFor && document.getElementById(label.htmlFor)) || label.querySelector('input, select, textarea')
        || label.parentElement.querySelector('input:not([type=hidden]), select, textarea');
      if (!campo || !campo.getClientRects().length) continue;
      const l = label.getBoundingClientRect(); const c = campo.getBoundingClientRect();
      if (campo.matches('[type=checkbox], [type=radio]')) continue;
      const hueco = c.top - l.bottom; const solape = Math.min(l.right, c.right) - Math.max(l.left, c.left);
      if (hueco < -2 || hueco > 16 || solape <= 0) fallos.push(`«${label.textContent.trim().slice(0, 25)}» a ${Math.round(hueco)} px de su campo`);
    }
    return fallos;
  },
  errores: () => {
    const W = window.innerWidth; const fallos = [];
    const campos = [...document.querySelectorAll('.main input:not([type=hidden]), .main select, .main textarea')].filter((c) => c.getClientRects().length);
    const errores = [...document.querySelectorAll('.main .field-hint-error, .main [role=alert]')].filter((e) => e.getClientRects().length && e.textContent.trim());
    if (!errores.length) fallos.push('el envío vacío no mostró ningún error');
    for (const e of errores) {
      const r = e.getBoundingClientRect();
      if (r.left < -1 || r.right > W + 1) fallos.push(`error fuera del ancho: «${e.textContent.trim().slice(0, 30)}»`);
      const previo = campos.filter((c) => c.compareDocumentPosition(e) & Node.DOCUMENT_POSITION_FOLLOWING).pop();
      if (previo && (r.top - previo.getBoundingClientRect().bottom > 32 || r.top - previo.getBoundingClientRect().bottom < -2)) fallos.push(`error lejos de su campo: «${e.textContent.trim().slice(0, 30)}»`);
    }
    return fallos;
  },
  menuCerrado: () => {
    const btn = document.querySelector('.menu-btn'); const aside = document.getElementById('menu-principal');
    return {
      boton: !!btn && btn.getClientRects().length > 0 && getComputedStyle(btn).display !== 'none',
      panelOculto: !!aside && getComputedStyle(aside).visibility === 'hidden',
    };
  },
  menuAbierto: () => {
    const W = window.innerWidth; const aside = document.getElementById('menu-principal');
    const dentro = (el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.left >= -1 && r.right <= W + 1 && getComputedStyle(el).visibility !== 'hidden'; };
    const enlaces = [...aside.querySelectorAll('nav a')];
    return {
      expandido: document.querySelector('.menu-btn').getAttribute('aria-expanded') === 'true',
      focoEnActual: document.activeElement?.matches('#menu-principal a[aria-current="page"]') ?? false,
      inerte: document.querySelector('main').inert === true,
      actuales: aside.querySelectorAll('a[aria-current="page"]').length,
      ocultos: [...enlaces, aside.querySelector('.user-name'), aside.querySelector('.btn-logout')].filter((el) => !el || !dentro(el)).length,
    };
  },
  focoEnBoton: () => document.activeElement?.classList.contains('menu-btn') ?? false,
  expandido: () => document.querySelector('.menu-btn')?.getAttribute('aria-expanded') === 'true',
  cerrar: () => {
    const b = document.querySelector('.menu-cerrar'); const r = b.getBoundingClientRect();
    return { visible: r.width > 0 && getComputedStyle(b).visibility !== 'hidden', ancho: r.width, alto: r.height, nombre: b.getAttribute('aria-label') ?? '' };
  },
  alcanzaFinal: () => {
    const aside = document.getElementById('menu-principal'); aside.scrollTop = aside.scrollHeight;
    const H = window.innerHeight; const ok = (el) => { const r = el.getBoundingClientRect(); return r.top >= -1 && r.bottom <= H + 1; };
    const enlaces = aside.querySelectorAll('nav a');
    return ok(enlaces[enlaces.length - 1]) && ok(aside.querySelector('.btn-logout'));
  },
  sinJs: () => {
    const d = (s) => { const el = document.querySelector(s); return el ? getComputedStyle(el).display : 'falta'; };
    const aside = document.querySelector('aside'); const main = document.querySelector('main');
    return {
      doc: document.documentElement.scrollWidth, vista: window.innerWidth, boton: d('.menu-btn'), cerrar: d('.menu-cerrar'),
      marca: d('.sidebar-marca'), posicion: getComputedStyle(aside).position,
      encima: main.getBoundingClientRect().top >= aside.getBoundingClientRect().bottom - 1,
    };
  },
  escritorio: () => {
    const d = (s) => { const el = document.querySelector(s); return el ? getComputedStyle(el).display : 'falta'; };
    const aside = document.querySelector('aside'); const r = aside.getBoundingClientRect();
    return { boton: d('.menu-btn'), velo: d('.menu-velo'), marca: d('.sidebar-marca'), lateral: r.width > 0 && getComputedStyle(aside).visibility !== 'hidden' };
  },
  // ── Spec 034 ──
  solapes: () => {
    const fallos = [];
    const visible = (el) => el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden';
    const nombre = (el) => `${el.tagName.toLowerCase()}.${[...el.classList].join('.')} «${(el.textContent || el.getAttribute('aria-label') || el.name || '').trim().slice(0, 20)}»`;
    const controles = [...document.querySelectorAll('.main a, .main button, .main input:not([type=hidden]), .main select, .main textarea')].filter(visible);
    const cajas = controles.map((c) => c.getBoundingClientRect());
    for (let i = 0; i < controles.length; i++) {
      for (let j = i + 1; j < controles.length; j++) {
        if (controles[i].contains(controles[j]) || controles[j].contains(controles[i])) continue;
        const a = cajas[i]; const b = cajas[j];
        if (Math.min(a.right, b.right) - Math.max(a.left, b.left) > 1 && Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top) > 1) fallos.push(`${nombre(controles[i])} se cruza con ${nombre(controles[j])}`);
      }
      const tarjeta = controles[i].closest('.tabla-tarjetas tr, .filtro-tarjeta, .timeline-item, .card');
      if (tarjeta) {
        const t = tarjeta.getBoundingClientRect(); const a = cajas[i];
        if (a.left < t.left - 1 || a.right > t.right + 1 || a.top < t.top - 1 || a.bottom > t.bottom + 1) fallos.push(`${nombre(controles[i])} sale de su tarjeta`);
      }
    }
    return [...new Set(fallos)].slice(0, 8);
  },
  tarjetas: () => {
    const fallos = [];
    for (const tabla of document.querySelectorAll('.main table')) {
      if (!tabla.getClientRects().length) continue;
      if (!tabla.classList.contains('tabla-tarjetas')) { fallos.push('una tabla visible no es tabla-tarjetas'); continue; }
      for (const tr of tabla.querySelectorAll('tbody tr')) {
        if (tr.querySelector('td[colspan]')) continue;
        const fondo = getComputedStyle(tr).backgroundColor;
        if (fondo === getComputedStyle(tr.parentElement).backgroundColor) fallos.push(`tarjeta${tr.classList.contains('fila-hoy') ? ' de hoy' : ''} con el fondo de su contenedor (${fondo})`);
        const cab = tr.querySelector('.celda-cab'); const estado = tr.querySelector('.celda-estado');
        if (cab && estado) {
          const a = cab.getBoundingClientRect(); const b = estado.getBoundingClientRect();
          if (Math.abs(a.top - b.top) > 6 || b.left < a.left) fallos.push('el estado no queda arriba a la derecha, junto a la cabecera');
        }
        for (const td of tr.querySelectorAll('td')) {
          if (!td.getClientRects().length || td.matches('.celda-cab, .celda-estado, .celda-acciones')) continue;
          const etiqueta = getComputedStyle(td, '::before').content;
          const texto = td.textContent.trim().replace(/\s+/g, ' ').slice(0, 20);
          if (!etiqueta || ['none', 'normal', '""'].includes(etiqueta)) { fallos.push(`«${texto}» sin etiqueta`); continue; }
          const r = td.getBoundingClientRect(); const cs = getComputedStyle(td);
          const columna = parseFloat(cs.gridTemplateColumns) || 0;
          const rango = document.createRange(); rango.selectNodeContents(td);
          const lineas = [...rango.getClientRects()].filter((x) => x.width > 0);
          if (!lineas.length) continue;
          if (Math.min(...lineas.map((x) => x.left)) < r.left + columna - 1) fallos.push(`«${texto}» invade la columna de su etiqueta`);
          if (Math.min(...lineas.map((x) => x.top)) > r.top + parseFloat(cs.paddingTop) + 6) fallos.push(`«${texto}» no empieza en la línea de su etiqueta`);
        }
      }
    }
    return [...new Set(fallos)].slice(0, 8);
  },
  escribirPalabraLarga: (larga) => {
    const nombres = /\b(Maximiliana|Diana|Carlos|Ana|Alba|Limpieza)\b/g;
    const recorrido = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    const nodos = [];
    for (let n = recorrido.nextNode(); n; n = recorrido.nextNode()) if (n.textContent.search(nombres) !== -1) nodos.push(n);
    for (const n of nodos) n.textContent = n.textContent.replace(nombres, larga);
    return nodos.length;
  },
  palabrasPartidas: (ambito = '.main, #menu-principal') => {
    const fallos = [];
    const contenido = (el) => { const cs = getComputedStyle(el); return el.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight); };
    const medidor = document.createElement('span');
    medidor.style.cssText = 'position:absolute;left:-9999px;top:0;visibility:hidden;white-space:nowrap';
    document.body.appendChild(medidor);
    const recorrido = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    const rango = document.createRange();
    for (let n = recorrido.nextNode(); n; n = recorrido.nextNode()) {
      const el = n.parentElement;
      if (!el || !el.closest(ambito) || el.closest('script, style') || !el.getClientRects().length || getComputedStyle(el).visibility === 'hidden') continue;
      const palabras = /[^\s\-–—/]+/g; let m;
      while ((m = palabras.exec(n.textContent))) {
        if (m[0].length < 2) continue;
        rango.setStart(n, m.index); rango.setEnd(n, m.index + m[0].length);
        const lineas = new Set([...rango.getClientRects()].filter((x) => x.width > 0).map((x) => Math.round(x.top)));
        if (lineas.size < 2) continue;
        const cs = getComputedStyle(el);
        medidor.style.font = cs.font; medidor.style.letterSpacing = cs.letterSpacing; medidor.style.textTransform = cs.textTransform;
        medidor.textContent = m[0];
        const ancho = medidor.getBoundingClientRect().width;
        // El ancho que el diseño da al texto: su tarjeta o bloque con ancho propio (D8, A38).
        const tarjeta = el.closest('.tabla-tarjetas tr') || el.closest('.card, .welcome-banner, .stat-card') || el.closest('#menu-principal') || document.querySelector('.content');
        let referencia = contenido(tarjeta);
        const td = el.closest('.tabla-tarjetas td');
        if (td && getComputedStyle(td).display === 'grid') referencia -= parseFloat(getComputedStyle(td).gridTemplateColumns) || 0;
        if (ancho <= referencia) fallos.push(`«${m[0].slice(0, 30)}» partida (mide ${Math.round(ancho)} px y su diseño le da ${Math.round(referencia)})`);
      }
    }
    medidor.remove();
    return [...new Set(fallos)].slice(0, 8);
  },
  filtro: () => {
    const f = document.querySelector('.filtro-citas');
    if (!f) return { falta: true };
    const panel = document.getElementById('filtro-panel'); const boton = f.querySelector('.filtro-btn');
    const buscador = f.querySelector('.filtro-buscar'); const atajos = f.querySelector('.filtro-atajos');
    const r = (el) => el.getBoundingClientRect(); const cs = getComputedStyle(f);
    const panelVisible = !!panel && getComputedStyle(panel).display !== 'none' && panel.getClientRects().length > 0;
    return {
      panelVisible, expandido: boton?.getAttribute('aria-expanded') ?? 'falta', boton: boton ? getComputedStyle(boton).display : 'falta',
      orden: !!(buscador && atajos && panel) && r(buscador).bottom <= r(atajos).top + 1 && (!panelVisible || r(atajos).bottom <= r(panel).top + 1),
      anchoBuscador: !!buscador && Math.abs(r(buscador).width - (f.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight))) <= 2,
      atajosEnUnaLinea: !!atajos && new Set([...atajos.children].filter((c) => c.getClientRects().length).map((c) => Math.round(r(c).top))).size === 1,
    };
  },
  detalle: () => {
    const d = document.querySelector('.detalle-cita');
    if (!d) return ['no hay .detalle-cita'];
    const fallos = []; let anterior = -Infinity;
    for (const bloque of [...d.children].filter((h) => h.getClientRects().length)) {
      const r = bloque.getBoundingClientRect();
      if (r.top < anterior - 1) fallos.push(`${bloque.className} fuera de orden o al lado de otro bloque`);
      anterior = r.bottom;
      if (Math.abs(r.width - d.clientWidth) > 2) fallos.push(`${bloque.className} no ocupa el ancho (${Math.round(r.width)} de ${d.clientWidth})`);
    }
    return fallos;
  },
  panelDentista: () => {
    const fallos = [];
    for (const item of document.querySelectorAll('.timeline-item')) {
      const titulo = item.querySelector('.timeline-title'); const acciones = item.querySelector('.timeline-acciones');
      if (!titulo || !acciones) { fallos.push('elemento de la línea de tiempo sin .timeline-acciones'); continue; }
      if (acciones.getBoundingClientRect().top < titulo.getBoundingClientRect().bottom - 1) fallos.push('el estado y el enlace no van bajo el nombre');
    }
    return [...new Set(fallos)];
  },
  botonMenu: () => {
    const b = document.querySelector('.menu-btn'); const r = b.getBoundingClientRect();
    return { texto: b.innerText.trim(), nombre: b.getAttribute('aria-label') ?? '', ancho: r.width, alto: r.height };
  },
  marca: () => {
    const m = document.querySelector('#menu-principal .sidebar-marca');
    if (!m) return { falta: true };
    const r = m.getBoundingClientRect();
    return { ancho: Math.round(r.width), fondo: getComputedStyle(m).backgroundColor, logo: getComputedStyle(document.documentElement).getPropertyValue('--logo-max-w').trim() };
  },
  sinJsPersonal: () => {
    const panel = document.getElementById('filtro-panel'); const boton = document.querySelector('.filtro-btn');
    return {
      doc: document.documentElement.scrollWidth, vista: window.innerWidth,
      panel: panel ? panel.getClientRects().length > 0 || getComputedStyle(panel).display === 'contents' : null,
      boton: boton ? getComputedStyle(boton).display : null,
    };
  },
  excluidos: () => [...document.querySelectorAll('.filtro-citas .field')].map((e) => {
    const r = e.getBoundingClientRect();
    return [Math.floor(r.left + scrollX - 4), Math.floor(r.top + scrollY - 4), Math.ceil(r.width + 8), Math.ceil(r.height + 8)];
  }),
};

// Compara dos PNG en un lienzo: proporción de píxeles distintos y mayor diferencia de un canal,
// sin contar los rectángulos excluidos (D13).
async function compararImagenes(lienzo, base, actual, fuera) {
  return lienzo.evaluate(async ({ a, b, fuera }) => {
    const carga = (src) => new Promise((ok, mal) => { const i = new Image(); i.onload = () => ok(i); i.onerror = mal; i.src = src; });
    const [ia, ib] = await Promise.all([carga(a), carga(b)]);
    if (ia.width !== ib.width || ia.height !== ib.height) return { tamano: `${ia.width}×${ia.height} frente a ${ib.width}×${ib.height}` };
    const lee = (img) => { const c = new OffscreenCanvas(img.width, img.height); const x = c.getContext('2d'); x.drawImage(img, 0, 0); return x.getImageData(0, 0, img.width, img.height).data; };
    const da = lee(ia); const db = lee(ib); const W = ia.width;
    const excluido = (x, y) => fuera.some(([l, t, w, h]) => x >= l && x < l + w && y >= t && y < t + h);
    let distintos = 0; let maximo = 0;
    for (let p = 0; p < da.length; p += 4) {
      const d = Math.max(Math.abs(da[p] - db[p]), Math.abs(da[p + 1] - db[p + 1]), Math.abs(da[p + 2] - db[p + 2]));
      if (!d) continue;
      const i = p / 4;
      if (fuera.length && excluido(i % W, Math.floor(i / W))) continue;
      distintos++; if (d > maximo) maximo = d;
    }
    return { proporcion: distintos / (da.length / 4), maximo };
  }, { a: `data:image/png;base64,${base.toString('base64')}`, b: `data:image/png;base64,${actual.toString('base64')}`, fuera });
}

const TOQUE_MAIN = '.main a, .main button, .main input:not([type=hidden]), .main select, .main textarea';
const TOQUE_MENU = '#menu-principal nav a, #menu-principal .menu-cerrar, #menu-principal .btn-logout';

async function abrir(rol, ruta, ancho, opciones) {
  const ctx = await contexto(rol, opciones);
  const page = await ctx.newPage();
  await page.setViewportSize(ANCHOS[ancho]);
  const resp = await page.goto(u(ruta), { waitUntil: 'networkidle' });
  if (page.url().includes('/login')) throw new Error('la sesión se perdió');
  if (resp && resp.status() >= 400) throw new Error(`HTTP ${resp.status()}`);
  return page;
}

async function enviarVacio(page, id) {
  const navegacion = page.waitForNavigation({ waitUntil: 'networkidle' });
  await page.evaluate((vista) => {
    const formulario = vista === 'cliente-agendar' ? document.querySelector('.main form')
      : vista === 'cliente-perfil' ? document.querySelector('.main input[name=nombre]').form
        : document.querySelector('.main input[name=formulario][value=reprogramar]').form;
    formulario.noValidate = true;
    for (const nombre of ['fecha', 'hora', 'nombre']) { const c = formulario.querySelector(`[name=${nombre}]`); if (c) c.value = ''; }
    setTimeout(() => formulario.submit(), 0);
  }, id);
  await navegacion;
}

async function medir() {
  const fallos = [];
  const falla = (donde, que) => fallos.push(`${donde}: ${que}`);
  const intenta = async (donde, fn) => { try { await fn(); } catch (e) { falla(donde, String(e.message).split('\n')[0]); } };
  let controlPositivo = false;

  for (const [id, ruta] of Object.entries(RUTAS_PACIENTE)) {
    for (const ancho of ['320', '390', '844x390', '900', '1024', '1440']) {
      await intenta(`${id}@${ancho}`, async () => {
        const page = await abrir('cliente', ruta, ancho);
        const a = await page.evaluate(EN_PAGINA.anchos);
        if (a.doc > a.vista) falla(`${id}@${ancho}`, `la página mide ${a.doc} px de ${a.vista}`);
        if (a.tablas) falla(`${id}@${ancho}`, `${a.tablas} tabla(s) más anchas que su contenedor`);
        if (['320', '390'].includes(ancho)) {
          if (!controlPositivo) {
            await page.evaluate(() => document.querySelector('.content').insertAdjacentElement('afterbegin', Object.assign(document.createElement('div'), {
              id: 'control-recorte', style: 'overflow:hidden;width:10px', innerHTML: '<button>Recortado a propósito</button>' })));
            if (!(await page.evaluate(EN_PAGINA.recortes)).some((r) => r.includes('Recortado'))) falla('control positivo', 'el detector de recortes no ve un botón recortado a propósito');
            await page.evaluate(() => document.getElementById('control-recorte').remove());
            controlPositivo = true;
          }
          for (const r of await page.evaluate(EN_PAGINA.recortes)) falla(`${id}@${ancho}`, `recortado: ${r}`);
        }
        if (ancho === '390') {
          for (const t of await page.evaluate(EN_PAGINA.toque, TOQUE_MAIN)) falla(`${id}@390`, `toque < 44: ${t}`);
          if (id === 'citas-reprogramar-cliente') for (const c of await page.evaluate(EN_PAGINA.casilla)) falla(`${id}@390`, c);
        }
        if (ancho === '320' && ['cliente-agendar', 'cliente-perfil', 'citas-reprogramar-cliente'].includes(id)) {
          for (const e of await page.evaluate(EN_PAGINA.etiquetas)) falla(`${id}@320`, `etiqueta: ${e}`);
          await enviarVacio(page, id);
          for (const e of await page.evaluate(EN_PAGINA.errores)) falla(`${id}@320`, e);
        }
        await page.close();
      });
    }
  }

  for (const [rol, ruta] of Object.entries(PANELES)) {
    for (const ancho of ['320', '390']) {
      await intenta(`menú ${rol}@${ancho}`, async () => {
        const donde = `menú ${rol}@${ancho}`;
        const page = await abrir(rol, ruta, ancho);
        const c = await page.evaluate(EN_PAGINA.menuCerrado);
        if (!c.boton) { falla(donde, 'no hay botón «Menú» visible'); await page.close(); return; }
        if (!c.panelOculto) falla(donde, 'el panel cerrado no está oculto');
        // El foco entra tras dos fotogramas, que con el navegador cargado pueden tardar más de 300 ms:
        // se espera por condición (T070). Si no llega en 2 s, la comprobación de abajo falla.
        const abrirlo = async () => {
          await page.locator('.menu-btn').click();
          await page.waitForFunction(() => document.activeElement?.matches('#menu-principal a[aria-current="page"]'), null, { timeout: 2000 }).catch(() => {});
          await page.waitForTimeout(300);
        };
        await abrirlo();
        const a = await page.evaluate(EN_PAGINA.menuAbierto);
        if (!a.expandido) falla(donde, 'aria-expanded no pasa a true');
        if (!a.focoEnActual) falla(donde, 'el foco no va a la sección actual');
        if (!a.inerte) falla(donde, '<main> no queda inerte');
        if (a.actuales !== 1) falla(donde, `${a.actuales} enlaces con aria-current`);
        if (a.ocultos) falla(donde, `${a.ocultos} secciones, nombre o «Cerrar sesión» no visibles`);
        if (ancho === '390') for (const t of await page.evaluate(EN_PAGINA.toque, TOQUE_MENU)) falla(donde, `toque < 44: ${t}`);
        await page.keyboard.press('Escape'); await page.waitForTimeout(300);
        if (await page.evaluate(EN_PAGINA.expandido)) falla(donde, 'Escape no cierra');
        if (!(await page.evaluate(EN_PAGINA.focoEnBoton))) falla(donde, 'tras Escape el foco no vuelve a «Menú»');
        // Si Escape no cerró, el velo tapa «Menú»: se recarga para seguir comprobando lo demás.
        if (await page.evaluate(EN_PAGINA.expandido)) await page.reload({ waitUntil: 'networkidle' });
        await abrirlo();
        const x = await page.evaluate(EN_PAGINA.cerrar);
        if (!x.visible) falla(donde, '«Cerrar menú» no se ve');
        if (x.ancho < 43.5 || x.alto < 43.5) falla(donde, `«Cerrar menú» mide ${Math.round(x.ancho)}×${Math.round(x.alto)}`);
        if (!x.nombre) falla(donde, '«Cerrar menú» sin nombre');
        if (x.visible) {
          await page.locator('.menu-cerrar').click(); await page.waitForTimeout(300);
          if (await page.evaluate(EN_PAGINA.expandido)) falla(donde, '«Cerrar menú» no cierra');
          if (!(await page.evaluate(EN_PAGINA.focoEnBoton))) falla(donde, 'tras «Cerrar menú» el foco no vuelve a «Menú»');
        }
        if (await page.evaluate(EN_PAGINA.expandido)) await page.reload({ waitUntil: 'networkidle' });
        await abrirlo();
        const { width, height } = ANCHOS[ancho];
        await page.mouse.click(width - 5, Math.round(height / 2)); await page.waitForTimeout(300);
        if (await page.evaluate(EN_PAGINA.expandido)) falla(donde, 'tocar el velo no cierra');
        await page.close();
      });
    }
    await intenta(`menú ${rol}@844x390`, async () => {
      const page = await abrir(rol, ruta, '844x390');
      if ((await page.evaluate(EN_PAGINA.menuCerrado)).boton) {
        await page.locator('.menu-btn').click(); await page.waitForTimeout(300);
        if (!(await page.evaluate(EN_PAGINA.alcanzaFinal))) falla(`menú ${rol}@844x390`, 'no se alcanzan el último enlace y «Cerrar sesión»');
      } else falla(`menú ${rol}@844x390`, 'no hay botón «Menú» visible');
      await page.close();
    });
    await intenta(`escritorio ${rol}@1440`, async () => {
      const page = await abrir(rol, ruta, '1440');
      const e = await page.evaluate(EN_PAGINA.escritorio);
      for (const [nombre, valor] of Object.entries({ botón: e.boton, velo: e.velo, marca: e.marca })) {
        if (valor !== 'none' && valor !== 'falta') falla(`escritorio ${rol}@1440`, `${nombre} visible (${valor})`);
      }
      if (!e.lateral) falla(`escritorio ${rol}@1440`, 'la barra lateral no se ve');
      await page.close();
    });
  }

  await intenta('sin JS cliente@390', async () => {
    const page = await abrir('cliente', '/cliente', '390', { js: false });
    const n = await page.evaluate(EN_PAGINA.sinJs);
    if (n.doc > n.vista) falla('sin JS cliente@390', `la página mide ${n.doc} px de ${n.vista}`);
    if (n.boton !== 'none') falla('sin JS cliente@390', n.boton === 'falta' ? 'no existe el botón «Menú»' : `«Menú» visible (${n.boton})`);
    if (n.cerrar !== 'none') falla('sin JS cliente@390', n.cerrar === 'falta' ? 'no existe «Cerrar menú»' : `«Cerrar menú» visible (${n.cerrar})`);
    if (n.marca === 'none' || n.marca === 'falta') falla('sin JS cliente@390', 'la marca de texto no se ve');
    if (n.posicion !== 'static' || !n.encima) falla('sin JS cliente@390', `el menú tapa el contenido (position: ${n.posicion})`);
    await page.close();
  });

  // ── Spec 034 ──────────────────────────────────────────────────────────────────────────────
  const INCLUIDAS_PACIENTE = ['cliente-dashboard', 'citas-index-cliente', 'citas-index-cliente-pasadas'];
  const pantallas = [
    ...PANTALLAS_PERSONAL,
    ...INCLUIDAS_PACIENTE.map((id) => [id, 'cliente', RUTAS_PACIENTE[id]]),
  ];
  let controlSolapes = false;
  for (const [id, rol, ruta] of pantallas) {
    for (const ancho of ['320', '390', '844x390', '1440']) {
      const donde = `${id}@${ancho}`;
      await intenta(donde, async () => {
        const page = await abrir(rol, ruta, ancho);
        const a = await page.evaluate(EN_PAGINA.anchos);
        if (a.doc > a.vista) falla(donde, `la página mide ${a.doc} px de ${a.vista}`);
        if (ancho === '1440') { await page.close(); return; }
        if (['320', '390'].includes(ancho) && rol !== 'cliente') for (const r of await page.evaluate(EN_PAGINA.recortes)) falla(donde, `recortado: ${r}`);
        for (const t of await page.evaluate(EN_PAGINA.toque, TOQUE_MAIN)) falla(donde, `toque < 44: ${t}`);
        if (!controlSolapes) {
          await page.evaluate(() => document.querySelector('.content').insertAdjacentHTML('afterbegin',
            '<div id="control-solape" style="position:relative"><button>Uno</button><button style="position:absolute;left:0;top:0">Dos</button></div>'));
          if (!(await page.evaluate(EN_PAGINA.solapes)).some((f) => f.includes('se cruza'))) falla('control positivo', 'el detector de solapes no ve dos botones encimados a propósito');
          await page.evaluate(() => document.getElementById('control-solape').remove());
          controlSolapes = true;
        }
        for (const f of await page.evaluate(EN_PAGINA.solapes)) falla(donde, `solape: ${f}`);
        for (const f of await page.evaluate(EN_PAGINA.tarjetas)) falla(donde, `tarjeta: ${f}`);
        if (/\/citas\/\{/.test(ruta)) for (const f of await page.evaluate(EN_PAGINA.detalle)) falla(donde, `detalle: ${f}`);
        if (ruta === '/dentista') for (const f of await page.evaluate(EN_PAGINA.panelDentista)) falla(donde, `panel: ${f}`);
        if (/\/citas$/.test(ruta) && ['320', '390'].includes(ancho)) {
          let f = await page.evaluate(EN_PAGINA.filtro);
          if (f.falta) { falla(donde, 'no hay .filtro-citas'); await page.close(); return; }
          if (ancho === '390') {
            if (f.panelVisible) falla(donde, 'el filtro no empieza plegado');
            if (f.expandido !== 'false') falla(donde, `«Filtros» empieza con aria-expanded=${f.expandido}`);
          }
          if (f.boton === 'none' || f.boton === 'falta') { falla(donde, 'no se ve «Filtros»'); await page.close(); return; }
          await page.locator('.filtro-btn').click(); await page.waitForTimeout(200);
          f = await page.evaluate(EN_PAGINA.filtro);
          if (!f.panelVisible) falla(donde, '«Filtros» no despliega el panel');
          if (f.expandido !== 'true') falla(donde, 'aria-expanded no pasa a true');
          if (!f.orden) falla(donde, 'el filtro no va en el orden buscador < atajos < panel');
          if (!f.anchoBuscador) falla(donde, 'el buscador no ocupa el ancho del formulario');
          if (!f.atajosEnUnaLinea) falla(donde, '«Esta semana», «Hoy» y «Filtros» no van en una línea');
          for (const s of await page.evaluate(EN_PAGINA.solapes)) falla(donde, `filtro desplegado: ${s}`);
          for (const r of await page.evaluate(EN_PAGINA.recortes)) falla(donde, `filtro desplegado, recortado: ${r}`);
          if (ancho === '390') {
            await page.keyboard.press('Tab');
            for (const [nombre, selector] of [['campo', '.filtro-citas .field'], ['«Hoy»', '.filtro-atajos a:nth-child(2)'], ['«Filtros»', '.filtro-btn'], ['«Filtrar»', '.filtro-citas button[type=submit]']]) {
              const foco = await focoVisible(page, selector);
              if (foco) falla(donde, `${nombre}: ${foco}`);
            }
            const tarjeta = await focoVisible(page, '.tabla-tarjetas .celda-acciones a, .tabla-tarjetas .celda-acciones button');
            if (tarjeta) falla(donde, `botón de tarjeta: ${tarjeta}`);
          }
        }
        if (ruta === '/dentista' && ancho === '390') {
          await page.keyboard.press('Tab');
          const foco = await focoVisible(page, '.btn-icono');
          if (foco) falla(donde, `enlace «Ver detalle»: ${foco}`);
        }
        await page.close();
      });
    }
  }

  // Palabras de 30 letras (D8): en todas las pantallas incluidas y en las 7 del paciente.
  let controlPalabras = false;
  const conPalabras = [...pantallas, ...Object.entries(RUTAS_PACIENTE).filter(([id]) => !INCLUIDAS_PACIENTE.includes(id)).map(([id, ruta]) => [id, 'cliente', ruta])];
  for (const [id, rol, ruta] of conPalabras) {
    for (const ancho of ['320', '390']) {
      const donde = `palabras ${id}@${ancho}`;
      await intenta(donde, async () => {
        const page = await abrir(rol, ruta, ancho);
        if (!controlPalabras) {
          await page.evaluate(() => document.querySelector('.content').insertAdjacentHTML('afterbegin',
            '<div class="card" id="control-palabras"><div style="width:20px">Palabra</div></div>'));
          if (!(await page.evaluate(EN_PAGINA.palabrasPartidas)).some((f) => f.includes('Palabra'))) falla('control positivo', 'el detector de palabras partidas no ve una palabra partida a propósito');
          await page.evaluate(() => document.getElementById('control-palabras').remove());
          controlPalabras = true;
        }
        if (!(await page.evaluate(EN_PAGINA.escribirPalabraLarga, LARGA))) falla(donde, 'no se encontró ningún nombre que alargar');
        const a = await page.evaluate(EN_PAGINA.anchos);
        if (a.doc > a.vista) falla(donde, `la página mide ${a.doc} px de ${a.vista}`);
        for (const r of await page.evaluate(EN_PAGINA.recortes)) falla(donde, `recortado: ${r}`);
        for (const f of await page.evaluate(EN_PAGINA.palabrasPartidas)) falla(donde, f);
        if (ancho === '390') {
          await page.locator('.menu-btn').click().catch(() => {}); await page.waitForTimeout(300);
          for (const f of await page.evaluate(EN_PAGINA.palabrasPartidas, '#menu-principal')) falla(`${donde} (menú)`, f);
        }
        await page.close();
      });
    }
  }

  // Menú: botón solo con icono y logo (D9, D10).
  for (const rol of Object.keys(PANELES)) {
    const donde = `034 menú ${rol}@390`;
    await intenta(donde, async () => {
      const page = await abrir(rol, PANELES[rol], '390');
      const b = await page.evaluate(EN_PAGINA.botonMenu);
      if (b.texto) falla(donde, `«Menú» muestra el texto «${b.texto}»`);
      if (b.nombre !== 'Menú') falla(donde, `«Menú» se llama «${b.nombre}»`);
      if (b.ancho < 43.5 || b.alto < 43.5) falla(donde, `«Menú» mide ${Math.round(b.ancho)}×${Math.round(b.alto)}`);
      await page.locator('.menu-btn').click(); await page.waitForTimeout(300);
      const m = await page.evaluate(EN_PAGINA.marca);
      if (m.falta) falla(donde, 'no hay marca en el panel');
      else {
        if (`${m.ancho}px` !== m.logo) falla(donde, `el logo mide ${m.ancho} px de ancho (--logo-max-w = ${m.logo})`);
        if (m.fondo !== 'rgb(153, 53, 86)') falla(donde, `el logo no se pinta con --p600 (${m.fondo})`);
      }
      await page.close();
    });
  }

  // Cambios de modo (033 T064 (a)–(d) y R21).
  await intenta('cambios de modo', async () => {
    const page = await abrir('cliente', '/cliente', '390');
    const enMenu = () => page.waitForFunction(() => document.activeElement?.matches('#menu-principal a[aria-current="page"]'), null, { timeout: 2000 }).catch(() => {});
    await page.locator('.menu-btn').click(); await enMenu();
    await page.locator('.menu-cerrar').focus();
    await page.setViewportSize({ width: 1000, height: 800 }); await page.waitForTimeout(300);
    if (!(await page.evaluate(() => document.activeElement?.matches('#menu-principal a[aria-current="page"]')))) falla('modo (a)', 'con el foco en «Cerrar menú», al pasar a 1000 px el foco no va al enlace actual');
    await page.locator('#menu-principal nav a').first().focus();
    await page.setViewportSize(ANCHOS['390']); await page.waitForTimeout(300);
    if (!(await page.evaluate(EN_PAGINA.focoEnBoton))) falla('modo (b)', 'con el foco en un enlace del menú, al pasar a 390 el foco no va a «Menú»');
    await page.locator('.menu-btn').focus(); await page.keyboard.press('Enter'); await page.keyboard.press('Escape'); await page.waitForTimeout(300);
    if (await page.evaluate(EN_PAGINA.expandido)) falla('modo (c)', 'Intro y Escape dejan el menú abierto');
    if (!(await page.evaluate(EN_PAGINA.focoEnBoton))) falla('modo (c)', 'tras Intro y Escape el foco no queda en «Menú»');
    // R21: a 1000 px, con el foco en un enlace de la barra lateral, un clic en una zona sin foco hace
    // olvidar el último foco; al pasar a 390, el foco no salta a «Menú» (reproducido el 2026-10-14).
    await page.setViewportSize({ width: 1000, height: 800 }); await page.waitForTimeout(300);
    await page.locator('#menu-principal nav a').nth(1).focus();
    const zona = await page.evaluate(() => { const r = document.querySelector('.content').getBoundingClientRect(); return [r.left + 3, r.top + 3]; });
    await page.mouse.click(zona[0], zona[1]); await page.waitForTimeout(100);
    await page.setViewportSize(ANCHOS['390']); await page.waitForTimeout(300);
    const activo = await page.evaluate(() => (document.activeElement === document.body ? 'body' : `${document.activeElement?.tagName.toLowerCase()} «${document.activeElement?.textContent.trim().slice(0, 20)}»`));
    if (activo !== 'body') falla('modo (R21)', `tras un clic en una zona sin foco, el cambio de modo mueve el foco a ${activo}`);
    // (d) Con el menú abierto, ir a otra sección y volver con «Atrás».
    await page.locator('.menu-btn').click(); await enMenu();
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('#menu-principal nav a:not([aria-current])').first().click()]);
    await page.goBack({ waitUntil: 'networkidle' }); await page.waitForTimeout(300);
    if (await page.evaluate(EN_PAGINA.expandido)) falla('modo (d)', 'al volver con «Atrás» el menú sigue abierto');
    if (await page.evaluate(() => document.querySelector('main').inert)) falla('modo (d)', 'al volver con «Atrás» <main> sigue inerte');
    await page.close();
  });

  // Sin JavaScript: las pantallas del personal (D6: el filtro desplegado y sin «Filtros»).
  for (const [id, rol, ruta] of PANTALLAS_PERSONAL) {
    const donde = `sin JS ${id}@390`;
    await intenta(donde, async () => {
      const page = await abrir(rol, ruta, '390', { js: false });
      const n = await page.evaluate(EN_PAGINA.sinJsPersonal);
      if (n.doc > n.vista) falla(donde, `la página mide ${n.doc} px de ${n.vista}`);
      if (/\/citas$/.test(ruta)) {
        if (n.panel !== true) falla(donde, 'el panel del filtro no se ve');
        if (n.boton === null) falla(donde, 'no existe el botón «Filtros»');
        else if (n.boton !== 'none') falla(donde, `«Filtros» visible sin JavaScript (${n.boton})`);
      }
      await page.close();
    });
  }

  // Escritorio frente a la línea base (D13).
  if (!LINEA_BASE) falla('línea base', 'falta LINEA_BASE (paso 7b de la cabecera)');
  else {
    const lienzo = await (await browser.newContext()).newPage();
    for (const [id, rol, ruta] of COMPARADAS) {
      for (const ancho of ['1024', '1440']) {
        const donde = `escritorio ${id}@${ancho}`;
        await intenta(donde, async () => {
          const archivo = `${LINEA_BASE}/${id}-${ancho}.png`;
          if (!fs.existsSync(archivo)) { falla(donde, `falta la línea base ${archivo}`); return; }
          const page = await abrir(rol, ruta, ancho);
          const actual = await page.screenshot({ fullPage: true, animations: 'disabled' });
          const fuera = await page.evaluate(EN_PAGINA.excluidos);
          await page.close();
          const c = await compararImagenes(lienzo, fs.readFileSync(archivo), actual, fuera);
          if (c.tamano) falla(donde, `distinto tamaño que la línea base: ${c.tamano}`);
          else if (c.proporcion > 0.001 || c.maximo > 2) falla(donde, `distinto de la línea base: ${(c.proporcion * 100).toFixed(2)} % de píxeles, diferencia máxima ${c.maximo}/255`);
        });
      }
    }
  }

  return fallos;
}

// Foco visible (034, A60, A68): enfoca el primer elemento del selector y mira su contorno.
async function focoVisible(page, selector) {
  const elemento = page.locator(selector).first();
  if (!(await elemento.count())) return `no hay ${selector}`;
  await elemento.focus();
  return elemento.evaluate((el) => {
    const cs = getComputedStyle(el);
    return cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) >= 2 ? '' : `sin foco visible (outline ${cs.outlineStyle} ${cs.outlineWidth})`;
  });
}

// Con vistas elegidas, solo esas y sin recortes de componentes (el resto de la galería no cambia).
const desconocidas = ELEGIDAS.filter((id) => !VISTAS.some(([v]) => v === id));
if (desconocidas.length) throw new Error(`vistas desconocidas: ${desconocidas.join(', ')}`);
const vistas = ELEGIDAS.length ? VISTAS.filter(([id]) => ELEGIDAS.includes(id)) : VISTAS;
const componentes = ELEGIDAS.length ? [] : COMPONENTES;

if (SECO && MEDIR_MODO) {
  console.log(COMPROBACIONES.map((c) => `- ${c}`).join('\n'));
  process.exit(0);
}
if (SECO) {
  console.log(`vistas (${vistas.length}):`);
  for (const [id, rol, ruta] of vistas) console.log(`  ${id} [${rol ?? 'invitado'}] ${ruta} · ${anchosDe(id).join(', ')}`);
  console.log(`componentes (${componentes.length}):`);
  for (const [nombre] of componentes) console.log(`  ${nombre}`);
  process.exit(0);
}

const { chromium } = await import('playwright');
const IDS = JSON.parse(fs.readFileSync('/tmp/capturas/ids.json', 'utf8'));
const browser = await chromium.launch();
const contextos = {};
async function contexto(rol, { js = true } = {}) {
  const clave = `${rol}${js ? '' : '-sin-js'}`;
  if (contextos[clave]) return contextos[clave];
  if (!js) {
    // Sin JS se entra con la sesión de un contexto con JS (el formulario de acceso usa JS).
    const conJs = await contexto(rol);
    contextos[clave] = await browser.newContext({ locale: 'es-MX', javaScriptEnabled: false, storageState: await conJs.storageState() });
    return contextos[clave];
  }
  const ctx = await browser.newContext({ locale: 'es-MX' });
  if (rol) {
    const p = await ctx.newPage();
    await p.goto(u('/login'));
    await p.fill('input[name=email]', rol === 'cliente' && CORREO_CLIENTE ? CORREO_CLIENTE : `${rol}1@dentissa.test`);
    await p.fill('input[name=password]', CLAVE);
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button[type=submit]')]);
    if (p.url().includes('/login')) throw new Error(`no se pudo entrar como ${rol}`);
    await p.close();
  }
  contextos[clave] = ctx;
  return ctx;
}

if (BASE_MODO) {
  if (OUT === '/design/capturas') throw new Error('--linea-base exige OUT fuera del repositorio (p. ej. /tmp/capturas/base)');
  fs.mkdirSync(OUT, { recursive: true });
  const errores = [];
  for (const [id, rol, ruta] of COMPARADAS) {
    for (const ancho of ['1024', '1440']) {
      try {
        const page = await abrir(rol, ruta, ancho);
        await page.screenshot({ path: `${OUT}/${id}-${ancho}.png`, fullPage: true, animations: 'disabled' });
        await page.close();
      } catch (e) { errores.push(`${id}@${ancho}: ${String(e.message).split('\n')[0]}`); }
    }
  }
  await browser.close();
  console.log(errores.length ? `${errores.length} errores:\n${errores.join('\n')}` : `línea base: ${COMPARADAS.length * 2} capturas en ${OUT}`);
  process.exit(errores.length ? 1 : 0);
}

if (MEDIR_MODO) {
  const fallos = await medir();
  fs.writeFileSync('/tmp/capturas/medir.json', JSON.stringify({ fallos }, null, 1));
  await browser.close();
  console.log(fallos.length ? `${fallos.length} fallos:\n${fallos.join('\n')}` : 'medir: todo en orden');
  process.exit(fallos.length ? 1 : 0);
}

fs.mkdirSync(`${OUT}/componentes`, { recursive: true });
const medidas = {}; const estado = {};
for (const [id, rol, ruta, accion] of vistas) {
  for (const ancho of anchosDe(id)) {
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
