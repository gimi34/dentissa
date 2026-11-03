---
spec: 041-recuperar-contrasena
verdict: changes_requested
round: 1
date: 2026-11-03
base: 7c472bf
head: 541eaa2
human_signoff: no requerido
---

# Review · 041 Recuperar contraseña

## Resumen
`changes_requested`: dos criterios de aceptación no se cumplen en un caso concreto (R1 y R2). El usuario decide
corregir además los tres importantes (R3, R4 y R5). Los menores van al roadmap, según el proceso ligero acordado
para la entrega. Revisores independientes por encargo; accesibilidad y SEO en un mismo subagente, y sin los
plugins `design:*`, que la sesión no ofrece.
Conteo: bloqueante 2 · importante 3 · menor 26

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 683 en verde, 11 833 aserciones (sin línea base de fallos) |
| `pint --test` sobre los 19 PHP del diff | ✅ |
| `node --test tests/js/` | ✅ 14 en verde |
| `npm run build` | no ejecutado: Vite no está instalado en este worktree; el diff no toca `package*`, `vite.config.js` ni `resources/js` o `resources/css` |
| `aidd.py validate` | ✅ 0 errores |
| `evaluate.mjs` con `criterios.json` sobre la build | ✅ las ocho páginas y «nueva-espera», 11 puertas y sus KPIs; `G-red` no se mide sobre URL y la cubre `test_sin_recursos_externos` |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RecuperarContrasenaTest::test_el_login_enlaza_a_recuperar`, `…_la_pantalla_de_solicitud_tiene_volver` | ✅ | |
| CA2 | `…_una_cuenta_activa_recibe_el_enlace`, `CorreosRestablecimientoTest::test_el_correo_del_enlace_tiene_caducidad_y_aviso` | ✅ | La entrega real la comprueba T095. Ver R24 |
| CA3 | `…_restablece_y_vuelve_al_login_con_exito`, `…_entra_con_la_nueva_y_no_con_la_anterior` | ❌ | Sin mensaje de éxito si ese navegador tenía abierta la cuenta (R1) |
| CA4 | `…_rechaza_menos_de_ocho_caracteres`, `…_rechaza_confirmacion_distinta_junto_al_campo` | ✅ | |
| CA5 | `…_rechaza_un_enlace_caducado`, `…_usado`, `…_sustituido` | ✅ | |
| CA6 | `…_una_cuenta_de_google_crea_su_contrasena_y_sigue_con_google` | ✅ | |
| CA7 | `SesionVigenteTest::test_restablecer_cierra_las_otras_sesiones` y siguientes | ✅ | |
| CA8 | `RecuperarContrasenaAbusoTest::test_misma_respuesta_…`, `…_la_respuesta_tarda_lo_mismo_…` | ✅ | Lo protege la aserción de 0 tokens antes de terminar (R15) |
| CA9 | `…_un_correo_por_cuenta_por_minuto`, `…_cinco_…`, `…_la_espera_desactiva_el_boton` | ❌ | Con un enlace bien formado pero inválido, la pantalla no pide esperar (R2) |
| CA10 | `…_rechaza_un_token_alterado`, `…_el_token_de_una_cuenta_no_cambia_…` | ✅ | |
| CA11 | `…_una_cuenta_desactivada_no_recibe_correo`, `…_no_puede_restablecer` | ✅ | |
| CA12 | `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado` | ✅ | |

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
| gitleaks v8.21.2 (47 commits) | ✅ 0 hallazgos con `.gitleaksignore`; sin la lista, 2 falsos positivos en `b9eb4aa` (contraseña ficticia de un test) |
| semgrep `p/php` | ✅ 0 hallazgos |
| `composer audit` + `npm audit` | crít: 0 · alta: 0 · media: 1 (`symfony/mime` CVE-2026-45070, línea base; no explotable en este flujo, R28) |
| trivy `fs` HIGH, CRITICAL | 1 alta, DS-0002 (sin `USER` no root) en `docker/prod/Dockerfile`, que este diff no toca. Con la imagen `latest`; la CI usa 0.57.1 y no bloquea. No es de esta spec |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | La autorización es el token de esa cuenta; vista previa solo en local |
| A02:2025 Configuración | ✅ | Interruptor apagado por defecto; cabeceras sin cambios |
| A03:2025 Cadena de suministro | ✅ | Sin cambios en lockfiles |
| A04:2025 Criptografía y datos | ✅ | bcrypt y token con hash; residuo en R25 |
| A05:2025 Inyección | ✅ | Bindings y `{{ }}`; semgrep confirmado |
| A06:2025 Diseño inseguro | ❌ | Sin tope de correos por hora ni por día (R5) |
| A07:2025 Autenticación | ❌ | Cambio de correo del perfil sin contraseña, encadenado con la recuperación (R4) |
| A08:2025 Integridad | ✅ | CSRF del grupo `web` |
| A09:2025 Registro y auditoría | ✅ | Evento crítico y logs sin datos; sin señal de abuso (R27) |
| A10:2025 Errores | ✅ | Falla cerrado: token inválido → «caducado»; auditoría caída → 503 y rollback |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, `contrasena_restablecida` (`RecuperarContrasenaAuditoriaTest`).
- Datos sensibles en logs del código tocado: ninguno; los `Log::` llevan solo la clase del error, y el log de acceso de nginx enmascara el token.
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

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `RecuperarContrasenaController.php:178`, `SesionVigente.php:27-32` | CA3: si el navegador que restablece tiene abierta esa cuenta, la contraseña cambia pero el mensaje de éxito se pierde: `SesionVigente` vacía la sesión al llegar a `/login`. Reproducido con una prueba temporal: el segundo `GET /login` no muestra «Tu contraseña cambió» | Cerrar esa sesión en `restablecer` antes de redirigir |
| R2 | bloqueante | `AppServiceProvider.php:87`, `RecuperarContrasenaController.php:90-91` | CA9: superado el límite al fijar, con un enlace bien formado pero inválido, la pantalla dice «Este enlace ya no sirve» y no pide esperar. Reproducido. El límite sí se aplica | Mostrar el aviso de espera en `caducado` |
| R3 | importante | `auth/recuperar/solicitud.blade.php`, `nueva.blade.php` | Tras un error de validación, un lector de pantalla no lo anuncia: sin foco ni región viva. Reproducido: sin `role="alert"` ni `autofocus` | Foco en el primer campo con error |
| R4 | importante | `PerfilController.php:48` | Cambiar el correo no pide contraseña (RS9.b). Con una sesión ajena, se dirige el enlace a otro buzón y se deja fuera al dueño, que no recibe aviso | Pedir la contraseña actual (decisión del usuario) |
| R5 | importante | `AppServiceProvider.php:76`, `config/auth.php:101` | Sin tope por hora ni por día: se puede agotar la cuota de Gmail, que comparte los avisos de producción, o bombardear un buzón e invalidar sus enlaces | 5 enlaces por cuenta por hora y 200 al día en total |
| R6 | menor | `public/css/acceso.css:141,145,146` | Medidas de la casilla con cuentas sobre tokens que no significan eso | Tokens con nombre |
| R7 | menor | `tests/Feature/CorreosRestablecimientoTest.php:93` | La cabecera `Host` falsa no llega a la petición; la protección la prueba otra aserción | Pedir con URL absoluta |
| R8 | menor | `resources/views/correos/acceso.css:8` | Con imágenes bloqueadas, el `alt` del logo sale en negro sobre la cabecera guinda | `color: #FFFFFF` en `.correo-cab` |
| R9 | menor | `resources/views/correos/acceso.css` (`.btn-primario`) | Outlook ignora el relleno de un `a` en bloque y reduce el botón | Botón con celda de tabla |
| R10 | menor | `auth/login.blade.php` | Con error de credenciales, el mismo texto se anuncia hasta tres veces | Un solo anuncio |
| R11 | menor | `public/css/acceso.css:154`, `docs/design/system.md:765` | El botón cede a `scale(.99)` y el sistema dice 0.98 | Alinearlos |
| R12 | menor | `public/css/acceso.css:89,165,196` | Literales que el test no revisa: `3px`, `16px`, `ease` | Tokens |
| R13 | menor | `public/css/acceso.css:51` | `--weight-regular` sin uso | Quitarlo |
| R14 | menor | `tests/Feature/RecuperarContrasenaVistasTest.php:376-382` | El valor del token se busca en todo `system.md`, no en su línea | Buscar el par en la misma línea |
| R15 | menor | `tests/Feature/RecuperarContrasenaAbusoTest.php:156` | El umbral de 300 ms con `Mail::fake()` apenas discrimina; protege la aserción de la línea 153 | Declararlo así en el plan |
| R16 | menor | `tests/Feature/RecuperarContrasenaAbusoTest.php:273-287` | El test de logs no comprueba que se capturó algo | `assertNotEmpty` |
| R17 | menor | `public/css/auth.css:3` | El comentario aún cita `login.blade.php` | Actualizarlo |
| R18 | menor | `app/Mail/*.php` | Paso a estilos en línea y cálculo de la hora duplicados; `CssToInlineStyles` es transitiva (D9) | Código común |
| R19 | menor | `auth/recuperar/*`, `correos/enlace-*` | «60 minutos» escrito a mano y la vigencia en `config` | Pasarlo como variable |
| R20 | menor | `auth/recuperar/solicitud.blade.php:14-17`, `nueva.blade.php:14-17` | Bloque de espera duplicado y mal sangrado | Vista parcial |
| R21 | menor | `RecuperarContrasenaController.php:197`, `AppServiceProvider.php:86` | UUID validado de dos maneras; el provider depende de un método del controlador | Unificar |
| R22 | menor | `tests/Feature/RecuperarContrasenaTest.php:182` | Variable `$caso` sin usar | Usarla en los mensajes |
| R23 | menor | `CorreosRestablecimientoTest.php:179-202`, `RecuperarContrasenaVistasTest.php` | Comprobador de tokens repetido en dos tests | Un solo comprobador |
| R24 | menor | `tests/Feature/RecuperarContrasenaTest.php:72` | Ningún test sigue el enlace del correo enviado ni comprueba el asunto | Test de punta a punta |
| R25 | menor | `config/session.php:50` | Al abrir el enlace, el token queda en `_previous.url` de la sesión sin cifrar | RS10.c y ampliar TM14 |
| R26 | menor | `routes/web.php:42` | El GET del enlace no tiene límite y puede ejecutar bcrypt | Limitador amplio |
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
- Cobertura de riesgos: RS9.b pasa a estar dentro de esta spec (CA13); su estado lo actualiza `/release`.

## Tareas añadidas
- T045, T046 ← R1
- T047, T048 ← R2
- T049, T050 ← R3
- T051, T052 ← R4
- T053, T054 ← R5

## Aceptados sin tarea
Aceptados por el proceso ligero para la entrega (decisión del usuario, 2026-10-31):
- **R6** → roadmap: medidas de la casilla con cuentas sobre tokens
- **R7** → roadmap: el test de TM12 no envía el `Host` falso
- **R8** → roadmap: `alt` del logo ilegible con imágenes bloqueadas en los correos
- **R9** → roadmap: botón del correo en Outlook
- **R10** → roadmap: anuncio repetido del error en el login
- **R11** → roadmap: `scale` del botón distinto del sistema
- **R12** → roadmap: literales fuera de lo que revisa el test
- **R13** → roadmap: `--weight-regular` sin uso
- **R14** → roadmap: test de tokens busca en todo `system.md`
- **R15** → roadmap: umbral de 300 ms poco discriminante
- **R16** → roadmap: test de logs sin comprobar que capturó algo
- **R17** → roadmap: comentario de `auth.css`
- **R18** → roadmap: duplicación en los correos y dependencia transitiva
- **R19** → roadmap: «60 minutos» escrito a mano
- **R20** → roadmap: bloque de espera duplicado
- **R21** → roadmap: UUID validado de dos maneras
- **R22** → roadmap: variable `$caso` sin usar
- **R23** → roadmap: comprobador de tokens repetido
- **R24** → roadmap: test de punta a punta del enlace del correo
- **R25** → roadmap: token en `_previous.url` de la sesión
- **R26** → roadmap: GET del enlace sin límite
- **R27** → roadmap: señal de abuso de los limitadores
- **R28** → roadmap: `symfony/mime` CVE-2026-45070
- **R29** → roadmap: test de contraste en la CI
- **R30** → roadmap: destino perdido con la sesión anulada
- **R31** → roadmap: fila del layout en el plan
