---
spec: 039-galeria-trabajos
verdict: changes_requested
round: 1
date: 2026-11-09
base: cf2a57d
head: a097006
human_signoff: pending
---

# Review · 039 Galería de trabajos

## Resumen
`changes_requested`. Un bloqueante: un PNG de 6000 × 6000 px pasa la validación pero agota los 256 MB de
PHP al decodificarlo y la subida acaba en un 500, sin mensaje y sin aviso en el log (CA8, CA12, D9;
reproducido). Hay además seis importantes, sobre todo de accesibilidad del diálogo y del formulario y tests
que no prueban del todo lo que dicen, y 26 menores. El usuario decidió corregir el bloqueante, los seis
importantes y seis menores (tareas T036–T050); los otros 20 menores van al roadmap.
La revisión la hicieron cuatro revisores independientes: backend y interfaz con perfil `critical`, en
dos paquetes porque el diff pasaba de 150 KB, y accesibilidad y SEO con perfil `standard`. Infraestructura,
alcance y preparación para el release, en la sesión principal. No hay plugin `design` en esta sesión:
la lista de accesibilidad se aplicó a mano, con los informes de `evaluate.mjs` como evidencia.
Conteo: bloqueante 1 · importante 6 · menor 26

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 816 en verde (13 210 aserciones); línea base 719, sin fallos previos |
| `node --test tests/js/publico.test.mjs` (dos veces) | ✅ 15/15 y 15/15 |
| `vendor/bin/pint --test` (30 PHP tocados) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/039-galeria-trabajos` | ✅ 0 errores |
| `design/medir.mjs` sobre las 7 pantallas de la app (T025, T027, T028, T030, T035) | ✅ todas las puertas y KPIs; K9 con 12 fotos 235 kB (`/`) y 278 kB (`/galeria`) |
| `node docs/design/capturar.mjs --medir` (T026, dos veces) | ✅ «todo en orden» |
| Lighthouse 13.5.0 SEO en `/galeria` | ✅ 100 en móvil y en escritorio |
| Build de assets | ➖ la 039 no toca Vite |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `CarruselInicioTest`, `publico.test.mjs` | ⚠ | el test de orden crea las fotos ya ordenadas (R6) |
| CA2 | `PaginaGaleriaTest::test_muestra_todas_en_orden_con_su_descripcion`, `::test_vacia_muestra_el_aviso` | ✅ | |
| CA3 | `CarruselInicioTest::test_sin_fotos_no_hay_carrusel_ni_enlace` | ✅ | comprobado rompiendo el `@if` |
| CA4 | `CarruselInicioTest::test_todas_las_fotos_estan_en_el_html` | ✅ | |
| CA5 | `GaleriaCssTest`, `PaginasPublicasCssTest`, puertas de `medir.mjs` | ✅ | con una sola foto, la escala queda fija (R8) |
| CA6 | `PaginaGaleriaTest::test_metadatos_de_galeria`, `PaginasPublicasTest` | ✅ | |
| CA7 | `PaginaGaleriaTest::test_con_sesion_se_ve_igual` | ✅ | |
| CA8 | `SubidaFotoGaleriaTest::test_publica_cada_formato_al_final` | ❌ | un PNG de 6000 px da 500 (R1) |
| CA9 | `GestionGaleriaTest` | ✅ | la declaración se busca en toda la página (aceptado, R25) |
| CA10 | `GestionGaleriaTest`, `PaginaGaleriaTest::test_la_foto_se_sirve_con_revalidacion` | ✅ | |
| CA11 | `SubidaFotoGaleriaTest::test_cada_falta_tiene_su_mensaje_y_no_publica` | ⚠ | solo se prueba en la sesión, no en la página (R4); el aviso no recibe el foco (R5) |
| CA12 | `SubidaFotoGaleriaTest` | ❌ | PNG grande (R1); más de 6 MB, 413 de nginx en inglés (R2) |
| CA13 | `GestionGaleriaAccesoTest` | ✅ | la segunda capa (Gate) sin test que falle (R11) |
| CA14 | `ProcesadorFotoTest::test_no_quedan_datos_de_la_camara`, `PaginaGaleriaTest::test_la_foto_descargada_no_tiene_exif` | ✅ | |
| CA15 | `PaginaGaleriaTest`, `CarruselInicioTest`, `GestionGaleriaTest` | ⚠ | en la gestión, una sola foto y sin comillas (R7) |
| CA16 | `AuditoriaGaleriaTest` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin tarea. `Dockerfile` sale en `scope.md` sin tarea porque el nombre suelto no se reconoce; lo cita T002. `docs/design/capturar.mjs` (T034) y `MovilPacienteTest` con el borde de `app.css` (T035) son tareas añadidas durante `/implement` con decisión del usuario.
- Partes del plan sin implementar: ninguna. Las tareas sin diff de código (T001, T029, T030, T031, T034) son documentación, verificación o scripts no versionados (`.ai/inspect/`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | spec, diseño, plan y tareas aprobados; `/analyze` r3 en `pass` |
| P2 Test que falla antes y pasa después | ✅ | T010–T019 y T032 antes de la implementación; los controles se comprobaron rompiéndolos (notas de T020, T023, T024 y T028). Tests débiles en R6, R7 y R11 |
| P3 Autorización en el servidor | ⚠ | `role:admin` más `Gate::authorize` en cada acción; probado rompiendo a mano en T023, pero sin test automático de la segunda capa (R11) |
| P4 Entrada validada y SQL con bindings | ❌ | la validación deja pasar un PNG que tumba el proceso (R1); por lo demás, `bail`, `mimes` por contenido, `whereUuid` y Eloquent |
| P5 Nada sensible en el repo | ✅ | imágenes de prueba generadas; gitleaks sin fugas |
| P6 Sin vulnerabilidades críticas o altas | ✅ | `trivy image` sin avisos nuevos; `composer audit` con EX4 (vence el 2026-11-11) |
| P7 Auditoría | ✅ | tres eventos en modo crítico y el 403 central; `AuditoriaGaleriaTest` |
| P8 Correlación y logs limpios | ✅ | `galeria.procesado_fallido` y `galeria.archivo_no_borrado` sin ruta ni descripción, con test |
| P9 Interfaz con el sistema de diseño | ⚠ | solo tokens y sin estilos en línea (`GaleriaCssTest`, `PaginasPublicasCssTest`); WCAG 2.4.3 en el diálogo (R3) |
| P10 Rutas indexables descubribles | ✅ | metadatos, canónica, un `h1`, un script con `defer`; Lighthouse SEO 100 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (`git log -p cf2a57d..HEAD`) | ✅ sin fugas |
| semgrep 1.99.0 `p/php` (PHP tocados) | ✅ 0 hallazgos |
| `composer audit` / `npm audit` | ✅ código 0 (6 avisos ignorados por EX4, vigente hasta el 2026-11-11) / 0 |
| `trivy fs` HIGH,CRITICAL · `trivy image` (T002) | ✅ sin hallazgos · producción 91 → 91, ninguno nuevo |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | TM1 y TM6: `role:admin`, Gate y `whereUuid`/`whereIn`; 403 registrado |
| A02 Fallos criptográficos y exposición | ✅ | TM4 sin datos de la cámara; TM8 `no-cache, private` y `noindex` con paciente |
| A05 Inyección / XSS | ✅ | todo `{{ }}`, `textContent`, sin sumideros en `publico.js`; TM5 con test (débil en la gestión, R7) |
| A06 Diseño inseguro / DoS de subida | ❌ | R1: un PNG válido agota la memoria |
| A08 Integridad del software y los datos | ✅ | solo píxeles recodificados, nunca el original |
| A09 Registro y monitorización | ✅ | auditoría crítica y logs saneados |
| Proceso | ✅ | sin dependencias de Composer o npm nuevas; `Dockerfile` y `AGENTS.md` (rutas protegidas) con aprobación; sin instrucciones sospechosas |

## Observabilidad
- Eventos de auditoría emitidos y probados: sí (`galeria_foto_publicada`, `_movida` y `_quitada` en modo crítico; `acceso_denegado` del registro central; `AuditoriaGaleriaTest`).
- Datos sensibles en logs del código tocado: ninguno (tests de los dos avisos sin ruta ni descripción).
- `request_id` en los logs nuevos: sí, por el `Context`.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Indexación y canónica | ✅ | `.ai/inspect/039-build/galeria.html`: canónica absoluta, sin `noindex` |
| Título y descripción | ✅ | propios y únicos (`PaginasPublicasTest`) |
| Contenido en el HTML del servidor | ✅ | fotos con `alt`, `width`, `height`, `srcset` y `sizes` |
| Enlazado interno | ⚠ | `/galeria` solo se enlaza desde el inicio con fotos (R30, aceptado) |
| Sitemap | ➖ | SE3, brecha conocida, ya anotada con `/galeria` |
- Sección «Descubribilidad» del plan aplicada: sí (`/galeria`, `/` y `/galeria/fotos/{foto}/{ancho}`, cada una con su test).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `app/Galeria/ProcesadorFoto.php:79`, `app/Http/Controllers/FotosGaleriaController.php:29` | Un PNG de 6000 × 6000 (RGB o RGBA, ≤ 5 MB) pasa la validación, pero GD lo decodifica con un búfer completo más la imagen: «Allowed memory size of 268435456 bytes exhausted» → 500, sin mensaje ni `galeria.procesado_fallido` (reproducido el 2026-11-09; un JPEG del mismo tamaño se queda en 177 MB). | Decisión del usuario: PNG como mucho 4000 px por lado, con su mensaje, antes de decodificar; test de memoria con un PNG RGBA de 4000 px |
| R2 | importante | `docker/nginx/dentissa.prod.conf:28` | Una foto de más de 6 MB recibe el 413 de nginx en inglés, fuera de la gestión, y no «La foto pesa más de 5 MB.» (CA12). | Decisión del usuario: `client_max_body_size 20m` en las dos configuraciones y en `verificar.sh` |
| R3 | importante | `resources/views/galeria/index.blade.php:111-125` | El diálogo declara `aria-modal`, pero el fondo sigue tabulable (WCAG 2.4.3). | `inert` en el contenido de fondo cuando hay diálogo, con test |
| R4 | importante | `resources/views/galeria/index.blade.php:76-105` | CA11 solo se prueba en la sesión: ningún test pinta la gestión con errores (mensaje junto al campo, `aria-invalid`, `aria-describedby` a ids existentes y valores conservados). | Test con `followingRedirects()` por cada falta |
| R5 | importante | `resources/views/galeria/index.blade.php:76-80` | El aviso de error llega con la página recargada, sin foco ni enlaces a los campos (WCAG 3.3.1). | Foco en el aviso (`tabindex="-1"` y `autofocus`) y enlaces a cada campo con error |
| R6 | importante | `tests/Feature/Galeria/CarruselInicioTest.php:66,81` | El orden se prueba con fotos creadas ya ordenadas: pasaría sin `ordenadas()`. | Crear las fotos desordenadas (2, 1, 3) |
| R7 | importante | `tests/Feature/Galeria/GestionGaleriaTest.php:238-248` | TM5 en la gestión con una sola foto: no prueba los `aria-label` de «Arriba» y «Abajo», ni comillas. | Tres fotos con la carga en la del medio, con `"` |
| R8 | menor | `public/js/publico.js:119`, `public/css/publico.css:893` | Con una sola foto, ningún `li` recibe `is-actual` y la foto queda ampliada al 104 % con JavaScript. | Marcar la única foto como actual |
| R9 | menor | `resources/views/publico/_trabajo.blade.php:8` | Con una foto de menos de 800 px, `srcset` repite el ancho (`600w, 600w`). | Un solo candidato en ese caso |
| R10 | menor | `resources/views/galeria/index.blade.php:104` | La ayuda del consentimiento no está enlazada a la casilla con `aria-describedby`. | `id` y `aria-describedby` como las demás ayudas |
| R11 | menor | `app/Http/Controllers/FotosGaleriaController.php:57` | Ningún test falla si se quita `Gate::authorize` de una acción: `role:admin` da antes el 403. | Test de las acciones sin `role:admin`, que espere el 403 del Gate |
| R12 | menor | `tests/Feature/Galeria/CarruselInicioTest.php:73-76` | `strpos` puede dar `false` y `false < int` es verdadero en PHP 8. | `assertNotFalse` antes de comparar |
| R13 | menor | `docs/specs/039-galeria-trabajos/plan.md:202, 239` | La trazabilidad cita tests que no existen con ese nombre (`test_el_resultado_es_webp_recodificado`, `test_el_grande_pesa_como_mucho_500_kb`). | Corregir los nombres |
| R14 | menor | `app/Galeria/Galeria.php:47` | Con la galería llena, la foto se procesa antes de comprobar el límite. | Comprobación barata antes del procesado |
| R15 | menor | `app/Galeria/Galeria.php:63` | El `lockForUpdate` del límite no tiene test; con la tabla vacía, dos subidas a la vez pueden acabar en *deadlock* y 500. | Reintentos de la transacción o capturar el *deadlock* |
| R16 | menor | `tests/Unit/Galeria/ProcesadorFotoTest.php:109` | Solo se prueban las orientaciones 3, 6 y 8, con un lienzo que no distingue arriba y abajo. | Lienzo de cuatro cuadrantes y orientaciones 2, 4, 5 y 7 |
| R17 | menor | `app/Galeria/ProcesadorFoto.php:28` | `TOPE_GRANDE = 512000` son 500 KiB, no los 500 kB de la spec y el ADR. | Usar 500 000 o decir KiB en los documentos |
| R18 | menor | `app/Http/Controllers/PaginasPublicasController.php:60` | Se lee el archivo entero antes de comparar el `ETag`. | `exists()` y `If-None-Match` antes de `get()` |
| R19 | menor | `tests/Feature/Galeria/AuditoriaGaleriaTest.php:109` | `test_el_evento_no_lleva_la_descripcion` pasaría sin ningún evento. | Comprobar antes que los eventos existen |
| R20 | menor | `app/Models/GaleriaFoto.php:72` | El literal `800` está repetido. | Una sola constante |
| R21 | menor | `tests/js/publico.test.mjs:165-176` | El carrusel falso ignora el desplazamiento a mano y `is-actual`. | Simular el desplazamiento y comprobar `href` y clase |
| R22 | menor | `tests/Feature/Galeria/GestionGaleriaTest.php:250-262` | TM9 no comprueba cuántos formularios de la gestión hay (el de cerrar sesión ya cumple). | Comprobar los formularios de `admin.galeria.*` |
| R23 | menor | `tests/Feature/Galeria/GaleriaCssTest.php:74-78` | Los valores de `--carrusel-foto-w` se buscan en toda la hoja, no en su corte. | Comprobarlos dentro de su `@media` |
| R24 | menor | `tests/Feature/Galeria/GaleriaCssTest.php:21` | La lista de la gestión no cubre `.galeria-gestion`, `.galeria-form`, `.campo-error`, `.btn-quitar`, `.modal-*` (solo usan tokens, revisado). | Añadirlos |
| R25 | menor | `tests/Feature/Galeria/GestionGaleriaTest.php:96-109` | La declaración de CA9 se busca en toda la página, no en su tarjeta. | Localizar la tarjeta con XPath |
| R26 | menor | `resources/views/publico/galeria.blade.php:34` | Por debajo de 560 px, la 2.ª y la 3.ª foto cargan sin esperar aunque quedan fuera de la primera pantalla. | Diferir desde la 2.ª en el móvil o documentarlo |
| R27 | menor | `public/css/publico.css:756` | `--foto-ratio` se declara en `app.css` y en `publico.css`. | Una sola fuente |
| R28 | menor | `resources/views/publico/_trabajo.blade.php` | El `alt` y el pie repiten la descripción (decisión de diseño). | Anotarlo en `design.md` o `alt=""` |
| R29 | menor | `public/js/publico.js:135-136` | «Anterior» y «Siguiente» son enlaces que con JavaScript actúan como botones. | Aceptar la mejora progresiva |
| R30 | menor | `resources/views/publico/inicio.blade.php:114` | `/galeria` solo se enlaza desde el inicio con fotos; ni la navegación ni el pie la enlazan. | Valorar un enlace en el pie |
| R31 | menor | `resources/views/publico/galeria.blade.php:19-23` | `/galeria` vacía es indexable con contenido casi vacío. | `noindex` solo sin fotos, si molesta |
| R32 | menor | `public/js/publico.js:140-152` | El desplazamiento a mano no anuncia la foto en la región de estado. | Opcional |
| R33 | menor | `docs/specs/039-galeria-trabajos/design.md:26` | Cita `galeria.css`; los estilos viven en `app.css` y `publico.css`. | Corregir la referencia |

## Preparación para release
- Rollback factible: sí. `deploy/volver.sh` a la versión anterior sin la copia; la migración solo crea `galeria_fotos` y la versión anterior la ignora.
- Migraciones compatibles con la versión anterior: sí (*expand*).
- Feature flags: no aplica (plan → Rollout).
- Docs actualizadas: sí (`architecture.md`, `deployment.md`, `observability.md`, `security.md`, `seo.md`; T090, T091 y T031).
- SCA: sin críticas ni altas fuera de EX4, que vence el 2026-11-11: antes del `/release` hace falta el `/chore` de Symfony 7.4.23 (condición de T096).
- Riesgos: RD10 pendiente, RS20.a y RS20.b en curso, RS20.c aceptada; ninguno figura como mitigado con correcciones pendientes.
- **La rama no contiene v0.14.2** (el fix del botón de alta de pacientes, en producción). Antes del `/release` hay que fusionar `main`, o la versión de la 039 desharía ese fix en producción.

## Tareas añadidas
- T036 ← R1, R13 (plan) · T037, T038 ← R1 · T039, T040 ← R2 · T041, T042 ← R3, R5, R10 · T043 ← R4 · T044 ← R6, R12 · T045 ← R7 · T046, T047 ← R8 · T048, T049 ← R9 · T050 ← R11

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-09 («corregir los que son fallos, el resto al roadmap»):
- **R14** → roadmap: con la galería llena, la foto se procesa antes de comprobar el límite.
- **R15** → roadmap: el bloqueo del límite de 12 no tiene test y dos subidas simultáneas con la tabla vacía pueden dar *deadlock*.
- **R16** → roadmap: orientaciones EXIF 2, 4, 5 y 7 sin test y lienzo que no distingue arriba y abajo.
- **R17** → roadmap: el tope del grande son 500 KiB y la spec dice 500 kB.
- **R18** → roadmap: la foto se lee entera antes de comparar el `ETag`.
- **R19** → roadmap: el test de la auditoría sin descripción no comprueba que haya eventos.
- **R20** → roadmap: literal `800` repetido.
- **R21** → roadmap: el test del carrusel no cubre el desplazamiento a mano ni `is-actual`.
- **R22** → roadmap: el test de CSRF no cuenta los formularios de la gestión.
- **R23** → roadmap: `GaleriaCssTest` busca los valores de `--carrusel-foto-w` fuera de su corte.
- **R24** → roadmap: `GaleriaCssTest` no cubre todas las reglas de la gestión.
- **R25** → roadmap: la declaración de CA9 se busca en toda la página.
- **R26** → roadmap: en el móvil, la 2.ª y la 3.ª foto de `/galeria` cargan sin esperar.
- **R27** → roadmap: `--foto-ratio` declarado en dos hojas.
- **R28** → roadmap: el `alt` repite el pie.
- **R29** → roadmap: los controles del carrusel son enlaces que actúan como botones.
- **R30** → roadmap: `/galeria` sin enlace en la navegación ni en el pie.
- **R31** → roadmap: `/galeria` vacía es indexable.
- **R32** → roadmap: el desplazamiento a mano no anuncia la foto.
- **R33** → roadmap: `design.md` cita `galeria.css`.
