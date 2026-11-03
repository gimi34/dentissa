---
spec: 038-pagina-inicio
verdict: changes_requested
round: 1
date: 2026-10-21
base: ed7e009
head: ed46634
human_signoff: no requerido
---

# Review · 038 Página de inicio pública

## Resumen
`changes_requested`: un bloqueante de P9 (interlineado y proporción del logo sin token del sistema,
y el test de literales no los detecta) y tres importantes (la placa de la dirección queda debajo
del mapa en el móvil; las aserciones de CA1 y CA5 buscan el horario y el teléfono en toda la
página, que desde D15 también los lleva en el pie; y un `assertDontSee('173')` que puede fallar por
el `?v=<filemtime>` de las hojas). El usuario corrige R1–R4 y doce menores baratos (T060–T068); los
otros trece menores van al roadmap. Base `ed7e009` (merge de T004) en lugar de `impl_base`
(`fba7a6d`), para no revisar el código de la 031 que trajo ese merge; T001, T002 y T004 son
documentación, fuentes y el merge. Cinco revisores con contexto limpio: A y S con `opus`; C+D,
accesibilidad y SEO con `sonnet`. La skill externa `design` no está instalada: la accesibilidad se
revisó con la lista de la skill.
Conteo: bloqueante 1 · importante 3 · menor 25

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, tras traer `main` con el `/chore` de `vite`) | ✅ 397 pasan (línea base 353, 0 fallos) |
| Tests nuevos de la 038, dos veces | ✅ 40 pasan las dos veces |
| `pint --test` sobre los 15 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/038-pagina-inicio` | ✅ 0 errores |
| `npm run build` | ➖ solo lo usa `welcome.blade.php`; se comprobó en el `/chore` de `vite` |
| `capturar.mjs --medir` (comprobación «038 páginas públicas») | ✅ «todo en orden» ×2 en T031; el `--medir` completo no es obligatorio (la 038 no toca `layouts/app.blade.php`, `menu.js` ni `movil.css`) |
| Lighthouse 13.5.0 (T042) | ✅ SEO 100 en `/` y `/servicios`, móvil y escritorio |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PaginaInicioTest::test_sin_sesion_muestra_la_pagina_en_orden` · `ExampleTest` | ⚠ | Pasa, pero el horario y el teléfono del aviso no se comprueban en su sección (R3) |
| CA2 | `PaginaInicioTest::test_con_sesion_cada_rol_va_a_su_panel` · `ServiciosPublicosTest::test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel` | ✅ | |
| CA3 | `PaginaInicioTest::test_sobre_nosotros_con_el_texto_aprobado` | ✅ | |
| CA4 | `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos`, `test_sin_servicios_activos_no_hay_avance` | ✅ | |
| CA5 | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar`, `test_el_mapa_es_perezoso_y_avisa_de_google` | ⚠ | El teléfono y el horario se buscan en toda la página (R3); la placa queda bajo el mapa en el móvil (R2); el horario se ve en el aviso y en el pie, no en la sección de ubicación (R12) |
| CA6 | `ServiciosPublicosTest` (9 casos) | ⚠ | `assertDontSee('173')` sobre el HTML entero (R4) |
| CA7 | `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` · `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` | ✅ | `app.css` no se revisa (R13) |
| CA8 | `PaginasPublicasTest::test_ninguna_pagina_publica_carga_scripts`, `test_el_contenido_va_en_el_html_del_servidor` | ✅ | |
| CA9 | `PaginasPublicasCssTest` · `capturar.mjs --medir` | ❌ | P9: literales de interlineado y proporción del logo (R1); placa bajo el mapa (R2) |
| CA10 | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`, `test_un_solo_h1` | ✅ | `/registro` no tiene descripción todavía (R5) |

## Conformidad con el plan
- Cambios fuera del plan: `package.json` y `package-lock.json` llegan del `/chore` de `vite` ya
  fusionado en `main` (PR #40, ficha `docs/changes/2026-10-20-chore-vite-8.3.4.md`); justificado.
- Partes del plan sin implementar: ninguna (T005 solo toca documentación del sistema).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes de código | ✅ | Spec, plan, tareas y refinado D15 aprobados |
| P2 Test que falla antes | ✅ | T010–T018 antes de T020–T032, con comprobaciones rompiendo lo protegido anotadas |
| P3 Autorización en el servidor | ✅ | `panelDelRol()` solo construye rutas fijas; la redirección por rol la prueba `PaginaInicioTest` |
| P4 Entrada validada y SQL con bindings | ✅ | Rutas sin parámetros; Eloquent; `test_parametros_en_la_url_no_cambian_la_lista` |
| P5 Nada sensible | ✅ | Contacto ficticio de `config/clinica.php`; gitleaks limpio; `OFL.txt` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | `composer audit`, `npm audit` (tras el `/chore` de `vite`) y trivy limpios |
| P7 Auditoría | ➖ | Sin datos sensibles, autenticación ni permisos |
| P8 Correlación y logs | ➖ | Sin logs nuevos |
| P9 Solo sistema de diseño, WCAG AA | ❌ | `public/css/publico.css`: `line-height` 1.5, 1.15 y 1.25 y `aspect-ratio: 454 / 135` sin token (R1); placa bajo el mapa en el móvil (R2) |
| P10 Rutas indexables descubribles | ❌ aceptado | JS de terceros del mapa (excepción del plan); JS propio 0 kB, SEO 100 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (`ed7e009..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP y Blade tocados) | ✅ 0 hallazgos |
| `composer audit` · `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy 0.57.1 `fs` HIGH,CRITICAL | ✅ 0 hallazgos |

| Tema OWASP (Top 10:2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | GET públicos sin parámetros; `panelDelRol()` sin redirección abierta; TM6 aceptado en `security.md` |
| A02 Configuración | ✅ | CSP solo cambia `frame-src`; `frame-ancestors 'none'` y `X-Frame-Options` fijados por test; `marcos.conf` con `default 'self'` y una regla anclada (TM3); `verificar.sh` interno sin `frame-src` (R14) |
| A03 Cadena de suministro | ✅ | Sin dependencias nuevas; lockfile del `/chore` con ficha; Lexend con licencia |
| A04 Criptografía y datos | ➖ | Sin datos sensibles |
| A05 Inyección | ✅ | Solo `{{ }}` en las vistas públicas (TM1, comprobado rompiendo); `rawurlencode` en las URL del mapa; variables entrecomilladas en `verificar.sh` |
| A06 Diseño inseguro | ✅ | TM1–TM6 con control y test |
| A07 Autenticación | ➖ | Sin cambios |
| A08 Integridad | ➖ | Solo GET |
| A09 Registro y monitoreo | ➖ | Sin logs nuevos |
| A10 Errores | ✅ | El `map` cae en `'self'` |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (la 038 no los define).
- Datos sensibles en logs del código tocado: ninguno (no añade logs).
- `request_id` presente en los logs nuevos: no aplica.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 1. Rastreo | ❌ aceptado | `robots.txt` sin `Sitemap:` y `sitemap.xml` 404 (SE3, objetivo 7) |
| 2. Códigos HTTP | ➖ | `/` y `/servicios` 200, inexistente 404, `/admin` sin sesión 302; 404 propia en SE4 |
| 3. Indexación y canónica | ✅ | Una canónica absoluta desde `APP_URL`, sin `noindex` (`seo/inicio.html`, `seo/servicios.html`) |
| 4. Metadatos por ruta | ✅ | `lang`, título, descripción, `viewport` y un `h1`; título de `/servicios` corto (R28) |
| 5. Contenido sin JavaScript | ✅ | Sin `<script>`; `test_ninguna_pagina_publica_carga_scripts` |
| 6. Imágenes y medios | ✅ | Sin `<img>`; logo `role="img"` con nombre; `iframe` con `title` y `loading="lazy"` |
| 7. Compartir | ➖ | Fuera de alcance (SE6) |
| 8. Datos estructurados | ➖ | Fuera de alcance (SE7) |
| 9. Idiomas | ➖ | Un solo idioma |
| 10. Rendimiento | ✅ | Lighthouse SEO 100; JS de terceros del mapa como excepción de P10 |
| 11. Entornos | no determinado | La canónica de producción solo la cubre el test (fuerza el dominio); se confirma en `/release` |
| 12. Íconos del sitio | ❌ aceptado | SE5 |

## Preparación para release
- Rollback: factible con `deploy/volver.sh` al tag anterior; sin migraciones ni datos nuevos.
- Migraciones: ninguna.
- Feature flags: el plan no los pide.
- `docs/architecture.md` y `docs/deployment.md`: actualizados (T090, T091).
- SCA: sin avisos críticos ni altos.
- Cobertura de riesgos: la 038 no declara ningún riesgo como mitigado; TM6 aceptado en `security.md`.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `public/css/publico.css:27, 51, 52, 88` · `tests/Feature/Concerns/ReglasCss.php` | P9: `line-height` 1.5, 1.15 y 1.25 (tipografía) y `aspect-ratio: 454 / 135` sin token del sistema; `literalesPublicos()` no los detecta y la cabecera del CSS dice que no hay literales | Tokens de interlineado y de proporción del logo en el sistema (1.7.2) y en `app.css`; ampliar el detector con controles positivos; corregir el comentario |
| R2 | importante | `public/css/publico.css:289` (`.lb-placa-dir` bajo 900 px) | A 390 px, con el mapa cargado, el `iframe` se pinta encima de la parte alta de la placa de la dirección (solape de 42 px; `elementFromPoint` devuelve el `IFRAME`) | Placa posicionada (encima del mapa) por debajo de 900 px, con su test de CSS y captura |
| R3 | importante | `tests/Feature/PaginaInicioTest.php:89-101, 153-160` | El horario y el teléfono se buscan en toda la página, que desde D15 los repite en el pie: quitar el teléfono de `#ubicacion` o el horario del aviso no rompe ningún test | Acotar las consultas a `#ubicacion` y a `.lb-aviso` |
| R4 | importante | `tests/Feature/ServiciosPublicosTest.php:97` | `assertDontSee('173')` busca en todo el HTML, que lleva `?v=<filemtime>` en las hojas: fallo intermitente posible | Comprobar sobre el texto de `<main>` |
| R5 | menor | `tests/Feature/PaginasPublicasTest.php:81-103` | La descripción de `/registro` no entra en la comparación de únicas (hoy no tiene, SE1.a) | Compararla si existe |
| R6 | menor | `tests/Feature/PaginaInicioTest.php:84` · `PaginasPublicasTest.php:159` | Exigen «Nuestros servicios» sin crear servicios: dependen del volcado | Crear un servicio propio del test |
| R7 | menor | `tests/Feature/PaginasPublicasTest.php:166` | `assertStringNotContainsString('opacity: 0')` no prueba nada útil | Quitarla |
| R8 | menor | `tests/Feature/ServiciosPublicosTest.php:111-124` | La agrupación sin acentos no se prueba (solo mayúsculas) | Un caso «Prótesis038» / «Protesis038» |
| R9 | menor | `tests/Feature/{PaginaInicio,ServiciosPublicos,PaginasPublicas}Test.php` | `xpath()`, `crearServicio()` y el alta de `Usuario` duplicados | Trait en `Concerns/` |
| R10 | menor | `resources/views/layouts/publico.blade.php:1` · `PaginasPublicasController.php:38` | La vista llama a un método estático del controlador | Mover `panelDelRol()` al modelo o a `app/Autorizacion/` |
| R11 | menor | `public/css/app.css` (`--green-texto`) | Token sin uso en la 038 (es para la 039 y la 040) | Anotarlo |
| R12 | menor | `resources/views/publico/inicio.blade.php:61-80` | CA5 pide el horario en el contacto; está en el aviso y en el pie, en `<dl>` y no con «·» | Aclararlo en la spec o añadirlo a la placa de la dirección |
| R13 | menor | `tests/Feature/PaginasPublicasTest.php:206-220` | Recursos externos y peso: solo revisan `publico.css`, no `app.css`, que el layout también carga | Recorrer las hojas enlazadas |
| R14 | menor | `deploy/verificar.sh:101-107` | Con el túnel parado no se comprueba el `frame-src` de `/` ni de `/servicios` | Añadirlos a la comprobación interna |
| R15 | menor | `resources/views/publico/inicio.blade.php:68` | `iframe` sin `sandbox` (endurecimiento) | Valorarlo y probar el mapa |
| R16 | menor | `tests/Feature/CabecerasSeguridadTest.php:217` | Faltan casos negativos `/;x`, `/.`, `/%3F`, `/index.php` | Añadirlos |
| R17 | menor | `resources/views/layouts/publico.blade.php:19-20` | El enlace del logo se llama «Inicio» y su imagen muestra la marca (WCAG 2.5.3) | Un solo nombre que incluya la marca |
| R18 | menor | `resources/views/layouts/publico.blade.php:35-47` | Bajo 900 px el menú no está dentro de una `nav` | Envolver el `<details>` en `nav` |
| R19 | menor | `resources/views/layouts/publico.blade.php` | Sin «Saltar al contenido» (2.4.1 se cumple con landmarks) | Opcional |
| R20 | menor | `resources/views/layouts/publico.blade.php:22-47` | Sin `aria-current="page"` | Añadirlo |
| R21 | menor | `resources/views/publico/inicio.blade.php:12` | El logo se anuncia tres veces en `/` | El de la placa, decorativo |
| R22 | menor | `resources/views/publico/inicio.blade.php:20` | «Aviso de la puerta» es jerga de la composición | Título más descriptivo (decisión de la spec) |
| R23 | menor | `resources/views/publico/inicio.blade.php:77` | «Cómo llegar» abre otra pestaña sin avisar (AAA) | Nombre accesible que lo diga |
| R24 | menor | `resources/views/publico/servicios.blade.php:33` | `<dd>` vacío si el servicio no tiene descripción (`descripcion` admite nulos) | Ocultar el `<dd>` vacío |
| R25 | menor | `public/css/publico.css:86, 92, 123, 137, 223, 234, 290` | `--touch-min` como unidad de tamaños que no son de toque, y `calc(var(--space-8) * -1.5)` | Tokens propios de tamaño |
| R26 | menor | `public/css/publico.css:44-47` | Regla de foco del panel duplicada de la base (por especificidad) | Comentario o selectores más simples |
| R27 | menor | `tests/Feature/PaginasPublicasTest.php:81-103` | `lang` y `viewport` solo se comprueban en el caso del layout | Añadirlos al test de metadatos |
| R28 | menor | `/servicios` (`<title>Servicios · Dentissa</title>`) | Título de 20 caracteres (orientativo 30–60) | Título más descriptivo (decisión de la spec) |
| R29 | menor | `resources/views/publico/inicio.blade.php:20` · layout (pie) | `h2` sin `id` ni `aria-labelledby` | Cosmético |


## Tareas añadidas
- T060 ← R1
- T061 ← R1, R2, R24
- T062 ← R1, R2, R24
- T063 ← R3, R4, R5, R6, R7, R8, R13, R27
- T064 ← R18, R21, R23, R24
- T065 ← R18, R21, R23, R24
- T066 ← R14, R16
- T067 ← R14
- T068 ← verificación final de las correcciones

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-21:
- **R9** → roadmap: trait de tests de las páginas públicas (`xpath()`, `crearServicio()`, alta de usuario).
- **R10** → roadmap: sacar `panelDelRol()` del controlador (modelo o `app/Autorizacion/`).
- **R11** → roadmap: `--green-texto` se usa con la 039 y la 040.
- **R12** → roadmap: aclarar en la spec que el horario de CA5 se ve en el aviso de la puerta y en el pie.
- **R15** → roadmap: `sandbox` en el `iframe` del mapa, probando que sigue funcionando.
- **R17** → roadmap: nombre del enlace del logo con la marca (WCAG 2.5.3).
- **R19** → roadmap: enlace «Saltar al contenido» en el layout público.
- **R20** → roadmap: `aria-current="page"` en la navegación pública.
- **R22** → roadmap: título del aviso más descriptivo que «Aviso de la puerta» (cambia texto de la spec).
- **R25** → roadmap: tokens de tamaño propios para logo, mapa, placa y menú en lugar de múltiplos de `--touch-min`.
- **R26** → roadmap: simplificar las reglas de foco de `publico.css`.
- **R28** → roadmap: título de `/servicios` más descriptivo (cambia texto de la spec).
- **R29** → roadmap: `id` y `aria-labelledby` en los `h2` del aviso y del pie.
