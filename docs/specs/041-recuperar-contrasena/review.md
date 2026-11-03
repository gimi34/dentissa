---
spec: 041-recuperar-contrasena
verdict: approved
round: 3
date: 2026-11-03
base: 1ef68aa
head: f6a9f79
human_signoff: no requerido
---

# Review · 041 Recuperar contraseña

## Resumen
`approved`: el usuario decidió corregir 7 menores antes de liberar (R7, R8, R10, R16, R26, R32 y R33). Están hechos,
con su test y comprobados rompiendo lo que protegen, sin bloqueantes ni importantes nuevos. Ronda `--rerun` sin
subagentes, por el tamaño del diff. T059 detectó y T060 corrigió que la respuesta del límite nuevo salía sin `no-store`.
Conteo: bloqueante 0 · importante 0 · menor 22 (aceptados)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 702 en verde, 12 009 aserciones (sin línea base de fallos) |
| `pint --test` sobre los 21 PHP del diff | ✅ |
| `node --test tests/js/` | ✅ 14 en verde |
| `npm run build` | no ejecutado: Vite no está instalado en este worktree; el diff no toca `package*`, `vite.config.js` ni `resources/js` o `resources/css` |
| `aidd.py validate` | ✅ 0 errores |
| `evaluate.mjs` con `criterios.json` sobre la build | ✅ las ocho páginas, «nueva-espera» y «caducado-espera», 11 puertas y sus KPIs; `G-red` no se mide sobre URL y la cubre `test_sin_recursos_externos` |
| Flujo real por nginx y PHP-FPM | ✅ correo, enlace, error junto al campo, restablecer, otra sesión cerrada, entra con la nueva y no con la anterior, enlace usado rechazado, evento de auditoría y token fuera del log de acceso |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RecuperarContrasenaTest::test_el_login_enlaza_a_recuperar`, `…_la_pantalla_de_solicitud_tiene_volver` | ✅ | |
| CA2 | `…_una_cuenta_activa_recibe_el_enlace`, `CorreosRestablecimientoTest::test_el_correo_del_enlace_tiene_caducidad_y_aviso` | ✅ | La entrega real la comprueba T095. Ver R24 |
| CA3 | `…_restablece_y_vuelve_al_login_con_exito`, `…_entra_con_la_nueva_y_no_con_la_anterior`, `SesionVigenteTest::test_con_la_cuenta_abierta_en_ese_navegador_se_ve_el_exito` | ✅ | R1 corregido |
| CA4 | `…_rechaza_menos_de_ocho_caracteres`, `…_rechaza_confirmacion_distinta_junto_al_campo` | ✅ | |
| CA5 | `…_rechaza_un_enlace_caducado`, `…_usado`, `…_sustituido` | ✅ | |
| CA6 | `…_una_cuenta_de_google_crea_su_contrasena_y_sigue_con_google` | ✅ | |
| CA7 | `SesionVigenteTest::test_restablecer_cierra_las_otras_sesiones` y siguientes | ✅ | |
| CA8 | `RecuperarContrasenaAbusoTest::test_misma_respuesta_…`, `…_la_respuesta_tarda_lo_mismo_…` | ✅ | Lo protege la aserción de 0 tokens antes de terminar (R15) |
| CA9 | `…_un_correo_por_cuenta_por_minuto`, `…_cinco_…`, `…_la_espera_desactiva_el_boton`, `…_la_espera_con_un_enlace_invalido_pide_esperar` | ✅ | R2 corregido |
| CA10 | `…_rechaza_un_token_alterado`, `…_el_token_de_una_cuenta_no_cambia_…` | ✅ | |
| CA11 | `…_una_cuenta_desactivada_no_recibe_correo`, `…_no_puede_restablecer` | ✅ | |
| CA12 | `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado` | ✅ | |
| CA13 | `PerfilCambioCorreoTest` | ✅ | Comprobado rompiendo el control; residuo en R32 |
| CA14 | `RecuperarContrasenaAbusoTest::test_cinco_enlaces_por_cuenta_por_hora`, `…_doscientos_enlaces_al_dia` | ✅ | Comprobado rompiendo el control |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los archivos que `scope.md` da sin tarea están cubiertos: `.gitleaksignore` (T042), `DisenioAccesoController` (D11), `auth/recuperar/partes/` (T039) y `TokensAcceso` (T017, T038).
- Partes del plan sin implementar: ninguna. Las tareas sin diff son de documentación o citan carpetas. La fila del layout en «Cambios por módulo» sigue describiendo la franja de `c7`; manda D20 (R31).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | `aidd.py status` |
| P2 Test primero | ✅ | Tests de cada tarea; sin `skip` |
| P3 Autorización en servidor | ✅ | `cuentaDelEnlace`: cuenta activa y token de esa cuenta |
| P4 Entrada y SQL | ✅ | `Validator` con `email:rfc`, `uuid`, `min(8)`; solo Eloquent y `where()` |
| P5 Nada sensible | ✅ | Solo contraseñas ficticias en tests |
| P6 Sin vulnerabilidades altas | ✅ | Sin cambios en dependencias; SCA abajo |
| P7 Auditoría | ✅ | `contrasena_restablecida` crítico dentro de la transacción |
| P8 Logs | ✅ | Solo la clase del error (R16 sobre su test) |
| P9 Sistema de diseño | ✅ | Tests de tokens y `evaluate.mjs`; P9 fija la verificación del contraste en `/review` (R29) |
| P10 Rutas indexables | ✅ | `noindex` en el layout de acceso, con test |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (64 commits) | ✅ 0 hallazgos con `.gitleaksignore`; sin la lista, 2 falsos positivos en `b9eb4aa` (contraseña ficticia de un test) |
| semgrep `p/php` | ✅ 0 hallazgos |
| `composer audit` + `npm audit` | crít: 0 · alta: 0 · media: 1 (`symfony/mime` CVE-2026-45070, línea base; no explotable en este flujo, R28) |
| trivy `fs` HIGH, CRITICAL | No repetido: esta ronda no toca dependencias ni imágenes. Ronda 1: 1 alta, DS-0002 (sin `USER` no root) en `docker/prod/Dockerfile`, que el diff no toca; con la imagen `latest`; la CI usa 0.57.1 y no bloquea |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | La autorización es el token de esa cuenta; vista previa solo en local |
| A02:2025 Configuración | ✅ | Interruptor apagado por defecto; cabeceras sin cambios |
| A03:2025 Cadena de suministro | ✅ | Sin cambios en lockfiles |
| A04:2025 Criptografía y datos | ✅ | bcrypt y token con hash; residuo en R25 |
| A05:2025 Inyección | ✅ | Bindings y `{{ }}`; semgrep confirmado |
| A06:2025 Diseño inseguro | ✅ | Topes de 5 por cuenta y hora y 200 al día, antes de crear el token (R5) |
| A07:2025 Autenticación | ✅ | El correo del perfil pide la contraseña actual, con 5 fallos por minuto, y esta no queda en la sesión (R4, R32); residuo en R32 |
| A08:2025 Integridad | ✅ | CSRF del grupo `web` |
| A09:2025 Registro y auditoría | ✅ | Evento crítico y logs sin datos; sin señal de abuso (R27) |
| A10:2025 Errores | ✅ | Falla cerrado: token inválido → «caducado»; auditoría caída → 503 y rollback |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, `contrasena_restablecida` (`RecuperarContrasenaAuditoriaTest`).
- Datos sensibles en logs del código tocado: ninguno; los `Log::` llevan solo la clase del error o ningún dato (`restablecimiento.tope_diario`), y el log de acceso de nginx enmascara el token.
- `request_id` presente en los logs nuevos: sí, por el `Context` de la 020.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Rastreo | ✅ | `robots.txt` permite rastrear, así que el `noindex` se lee |
| Indexación | ✅ | `noindex` en login y recuperación; `/registro` sigue indexable |
| Sitemap | ➖ | `/sitemap.xml` da 404 (SE3, ya anotada) |
| Títulos | ✅ | Únicos por pantalla |
| Contenido sin JavaScript | ✅ | HTML completo del servidor |
| Canónica y descripción de `/registro`, íconos | ❌ conocido | SE1 y SE5 de `docs/seo.md`, no son regresión de esta spec |
| Rendimiento | no determinado | No medido en esta ronda |
- Sección "Descubribilidad" del plan aplicada: sí, con `test_las_pantallas_nuevas_no_se_indexan` y `test_el_login_no_se_indexa`.

## Seguimiento de las rondas anteriores
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | T045, T046: `RecuperarContrasenaController::restablecer` cierra la sesión de esa cuenta; `test_con_la_cuenta_abierta_en_ese_navegador_se_ve_el_exito` |
| R2 | corregido | T047, T048: aviso de espera en `caducado`; `test_la_espera_con_un_enlace_invalido_pide_esperar`; `evaluate.mjs` «caducado-espera» |
| R3 | corregido | T049, T050: `autofocus` en el primer campo con error; `test_el_foco_va_al_primer_campo_con_error` |
| R4 | corregido | T051, T052: `PerfilCambioCorreoTest`, comprobado rompiendo `Hash::check`, la condición y `dontFlash`; formulario confirmado por el usuario |
| R7, R16 | corregido | T055: el host falso llega a la petición y el test de logs provoca un registro; comprobados rompiendo |
| R8 | corregido | T055, T056: `.correo-cab` fija el color del `alt` |
| R10, R33 | corregido | T057, T058: un solo anuncio del error en el login; la ayuda del perfil no se repite |
| R26 | corregido | T059, T060: `abrir-enlace`, 30 por minuto por origen, con `no-store` en su respuesta (TM11) |
| R32 | corregido en parte | T059, T060: 5 fallos por minuto por cuenta en los dos formularios del perfil. Queda la creación de contraseña en cuentas de Google |
| R5 | corregido | T053, T054: `test_cinco_enlaces_por_cuenta_por_hora`, `test_doscientos_enlaces_al_dia`, comprobados rompiendo cada tope |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R32 | menor | `PerfilController.php:96` | Residuo de D21: con una sesión ajena de una cuenta de Google se puede crear la contraseña en el perfil y cambiar luego el correo (RS5.c) | Reautenticar al crear la contraseña |
| R6 | menor | `public/css/acceso.css:141,145,146` | Medidas de la casilla con cuentas sobre tokens que no significan eso | Tokens con nombre |
| R9 | menor | `resources/views/correos/acceso.css` (`.btn-primario`) | Outlook ignora el relleno de un `a` en bloque y reduce el botón | Botón con celda de tabla |
| R11 | menor | `public/css/acceso.css:154`, `docs/design/system.md:765` | El botón cede a `scale(.99)` y el sistema dice 0.98 | Alinearlos |
| R12 | menor | `public/css/acceso.css:89,165,196` | Literales que el test no revisa: `3px`, `16px`, `ease` | Tokens |
| R13 | menor | `public/css/acceso.css:51` | `--weight-regular` sin uso | Quitarlo |
| R14 | menor | `tests/Feature/RecuperarContrasenaVistasTest.php:376-382` | El valor del token se busca en todo `system.md`, no en su línea | Buscar el par en la misma línea |
| R15 | menor | `tests/Feature/RecuperarContrasenaAbusoTest.php:156` | El umbral de 300 ms con `Mail::fake()` apenas discrimina; protege la aserción de la línea 153 | Declararlo así en el plan |
| R17 | menor | `public/css/auth.css:3` | El comentario aún cita `login.blade.php` | Actualizarlo |
| R18 | menor | `app/Mail/*.php` | Paso a estilos en línea y cálculo de la hora duplicados; `CssToInlineStyles` es transitiva (D9) | Código común |
| R19 | menor | `auth/recuperar/*`, `correos/enlace-*` | «60 minutos» escrito a mano y la vigencia en `config` | Pasarlo como variable |
| R20 | menor | `auth/recuperar/solicitud.blade.php:14-17`, `nueva.blade.php:14-17` | Bloque de espera duplicado y mal sangrado | Vista parcial |
| R21 | menor | `RecuperarContrasenaController.php:197`, `AppServiceProvider.php:86` | UUID validado de dos maneras; el provider depende de un método del controlador | Unificar |
| R22 | menor | `tests/Feature/RecuperarContrasenaTest.php:182` | Variable `$caso` sin usar | Usarla en los mensajes |
| R23 | menor | `CorreosRestablecimientoTest.php:179-202`, `RecuperarContrasenaVistasTest.php` | Comprobador de tokens repetido en dos tests | Un solo comprobador |
| R24 | menor | `tests/Feature/RecuperarContrasenaTest.php:72` | Ningún test sigue el enlace del correo enviado ni comprueba el asunto | Test de punta a punta |
| R25 | menor | `config/session.php:50` | Al abrir el enlace, el token queda en `_previous.url` de la sesión sin cifrar | RS10.c y ampliar TM14 |
| R27 | menor | `AppServiceProvider.php:76` | Las esperas de los limitadores nuevos no dejan señal | Línea en el canal `seguridad` |
| R28 | menor | `composer.lock:4671` | `symfony/mime` sigue con CVE-2026-45070 (media) y la CI no lo señala | `/chore` |
| R29 | menor | `tests/Feature/RecuperarContrasenaVistasTest.php:33` | El contraste no tiene test en la CI: lo mide `evaluate.mjs`. El revisor lo marcó importante; se baja porque P9 fija esa verificación en `/review` y pasa | Test con `contraste()` |
| R30 | menor | `SesionVigente.php:32` | Con la sesión anulada por un restablecimiento en otro equipo, el primer clic en un enlace lleva a `/login` y pierde el destino | Conservar el destino |
| R31 | menor | `plan.md:56` | «Cambios por módulo» aún describe la franja de `c7`; manda D20 | Actualizar la fila |

## Preparación para release
- Rollback factible: sí. Primero `RECUPERAR_CONTRASENA=false`; si hace falta, `deploy/volver.sh`.
- Migraciones compatibles con la versión anterior: sí. `version_sesion` es solo expand, con valor por defecto.
- Interruptor: existe y está apagado por defecto.
- Docs actualizadas (architecture, deployment): sí (T090, T091).
- SCA: sin críticas ni altas.
- Cobertura de riesgos: RS9.b está dentro de esta spec e implementada (CA13); su estado lo actualiza `/release`.

## Tareas añadidas
De la ronda 1, todas hechas:
- T045, T046 ← R1
- T047, T048 ← R2
- T049, T050 ← R3
- T051, T052 ← R4
- T053, T054 ← R5
- T055, T056 ← R7, R8, R16
- T057, T058 ← R10, R33
- T059, T060 ← R26, R32

## Aceptados sin tarea
Aceptados por el proceso ligero para la entrega (decisión del usuario, 2026-10-31):
- **R6** → roadmap: medidas de la casilla con cuentas sobre tokens
- **R9** → roadmap: botón del correo en Outlook
- **R11** → roadmap: `scale` del botón distinto del sistema
- **R12** → roadmap: literales fuera de lo que revisa el test
- **R13** → roadmap: `--weight-regular` sin uso
- **R14** → roadmap: test de tokens busca en todo `system.md`
- **R15** → roadmap: umbral de 300 ms poco discriminante
- **R17** → roadmap: comentario de `auth.css`
- **R18** → roadmap: duplicación en los correos y dependencia transitiva
- **R19** → roadmap: «60 minutos» escrito a mano
- **R20** → roadmap: bloque de espera duplicado
- **R21** → roadmap: UUID validado de dos maneras
- **R22** → roadmap: variable `$caso` sin usar
- **R23** → roadmap: comprobador de tokens repetido
- **R24** → roadmap: test de punta a punta del enlace del correo
- **R25** → roadmap: token en `_previous.url` de la sesión
- **R27** → roadmap: señal de abuso de los limitadores
- **R28** → roadmap: `symfony/mime` CVE-2026-45070
- **R29** → roadmap: test de contraste en la CI
- **R30** → roadmap: destino perdido con la sesión anulada
- **R31** → roadmap: fila del layout en el plan
- **R32** → roadmap: contraseña creada en el perfil de una cuenta de Google con una sesión ajena (RS5.c)
