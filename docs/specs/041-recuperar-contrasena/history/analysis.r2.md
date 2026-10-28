---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-10-28
spec_sha: 8456cad72e93
plan_sha: 5f1109882063
tasks_sha: 86eeb51c9ef1
---

# Análisis · 041 Recuperar contraseña

## Resumen
Resultado **fail**, por correcciones pequeñas.
- `/plan --fix` y `/tasks --fix` resuelven los 20 hallazgos abiertos de la ronda 1, incluido el
  crítico A1. Cuatro dejan efectos nuevos: A2 → A27, A4 → A24–A26 y A31, A6 → A28, A15 → A27.
- El hallazgo alto nuevo es A28: el `error_log` de nginx escribe el token cuando PHP falla. El usuario
  decide aceptarlo como excepción acotada a P8, y eso hay que registrarlo en el plan.
- También hay que corregir en el plan los medios A24–A27 y A29, y el bajo A30. A31 y A32 se
  proponen como notas de tarea.
- Delta de 480 líneas (106 KB), revisado por un subagente independiente (perfil `standard`,
  `sonnet`), sin cambio estructural.
- Validador: 0 errores, con dos avisos: el plan y las tareas citan SE2 y OB4, que la spec no cita
  (decisión del usuario sobre SE2.a; residuo de OB4.a).

Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

Conteo (nuevos): CRÍTICA 0 · ALTA 1 · MEDIA 5 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (CA7 y CA9 ya sin huecos) |
| Amenazas con control y test | 14 / 14 (TM13 y TM14 nuevas) |
| Principios de la constitución evaluados | 10 / 10 (los afectados por el delta; P8 pasa a ❌ aceptado con A28; P9 queda ✅ cuando A29 fije las exenciones) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | D13 y T001 llevan los 12 tokens que `c7` usa y no están en `main` (7 propios y 5 de la 038, con el valor de la 038); T017 `…_cada_var_del_acceso_tiene_su_token`; versión «siguiente libre» (D14) |
| A2 | ALTA | resuelto con efecto nuevo (→ A27) | D14 cubre `system.md`, nginx, `public/fonts/`, `AGENTS.md`, spec 001 y rutas; falta `config/clinica.php` |
| A3 | ALTA | resuelto | D4 («sin versión = 0»), T015 con los dos casos, T032, TM8 y Rollout alineados |
| A4 | ALTA | resuelto con efecto nuevo (→ A24, A25, A26, A31) | D15: limitadores solo en los `POST`, redirección a la pantalla del formulario, T012 `…_no_redirige_en_bucle`, estado de espera en T029 |
| A5 | ALTA | resuelto | T011 `…_restablecer_envia_el_aviso_a_la_cuenta`, T014 `…_sin_registro_no_se_envia_el_aviso` |
| A6 | ALTA | resuelto con efecto nuevo (→ A28) | D7 y T018 con `map $http_referer`; T017 exige `no-referrer` antes de cualquier recurso, también en «caducado» y en la espera; queda el `error_log` |
| A7 | ALTA | resuelto | D9 sin excepción a P9 (decisión del usuario); T016 y T017 sobre todos los valores; T020 comprobado rompiendo |
| A8 | ALTA | resuelto | T017 `…_acceso_css_sin_literales_fuera_de_root`; T024 comprobado rompiendo |
| A9 | ALTA | resuelto | D2 fija las reglas del contrato; T002 comprueba el TM10 actualizado de la 020 (prerrequisito `/plan 020 --fix`, fuera de la 041) |
| A10 | MEDIA | resuelto | T004 pone el interruptor en `phpunit.xml`; T010 recrea la aplicación (detalle en A32) |
| A11 | MEDIA | resuelto | T011 activa el CSRF de verdad; T029 comprueba rompiendo TM10 y TM11 |
| A12 | MEDIA | resuelto | T012 limpia el limitador entre peticiones y exige redirección a `contrasena.enviada` |
| A13 | MEDIA | resuelto | «Riesgos» del plan declara el mailer `log` en local y OB4.a |
| A14 | MEDIA | resuelto | T016 y T021: dirección de respaldo (URL) en HTML y en texto |
| A15 | MEDIA | resuelto con efecto nuevo (→ A27) | D16 y T006; choca con el `clinica.contacto` de la 038 |
| A16 | MEDIA | resuelto | D9 declara y verifica `tijsverkoyen/css-to-inline-styles` v2.4.0; P6 reescrito |
| A17 | MEDIA | resuelto | P10, Descubribilidad, T033 y T040 (SE2.a mitigada, SE2.b pendiente, SE2 parcial) |
| A18 | MEDIA | resuelto | T040 y la cabecera de `tasks.md` piden aprobación para `AGENTS.md` |
| A21 | BAJA | resuelto | TM13 y TM14 con control y test (T029/T011, T027/T010); la aceptación del residuo de TM13, en A30 |
| A23 | BAJA | resuelto | Nombres de rutas, métodos y vistas idénticos en el plan y en T026, T027, T029 y T033; ADR 0008 `accepted` |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A24 | Inconsistencia | MEDIA | `plan.md` → Estrategia de pruebas («los errores de campo … no gastan intentos») frente a D15 («un error de campo gasta un solo intento»); T012 | El limitador cuenta todo `POST`, también los que no pasan la validación, y el plan se contradice al decir lo que gasta un error. | `/plan 041 --fix A24` y `/tasks 041 --fix A24`: cuenta todo `POST`, válido o no. Test: 5 `POST` con un campo inválido hacen que el sexto muestre la espera |
| A25 | Seguridad | MEDIA | D15; T028 | La respuesta del limitador corre antes de validar y arma la redirección con el `token` y la `cuenta` de la petición. Con entrada malformada (`token[]`, valores enormes o con caracteres raros) puede dar un 500 o una URL distinta de la esperada. | `/plan 041 --fix A25` y `/tasks 041 --fix A25`: con entrada malformada, la respuesta al límite no da error y solo lleva a `contrasena.nueva` con un token y una cuenta de formato válido; si no lo son, lleva a `contrasena.solicitud`. Test en T012 con esas entradas |
| A26 | Seguridad / ambigüedad | MEDIA | `plan.md` → Contratos (`POST /contrasena/nueva`, «302 atrás»); `no-referrer` (T029) | Con `no-referrer`, el `POST` no envía `Referer`, y `back()` o la redirección por validación usan la URL previa de la sesión. Con dos pestañas puede ser otra página y se pierde el token. Los tests con `->from()` no lo ven. | `/plan 041 --fix A26` y `/tasks 041 --fix A26`: un error de campo vuelve siempre a `contrasena.nueva` con el token y la cuenta del formulario. Test: `POST` sin `Referer` y con otra URL como último `GET` de la sesión, y el error sale junto al campo en la URL del token |
| A27 | Duplicación y conflicto (038) | MEDIA | D14 (no lista `config/clinica.php`); D16; T006 | La 038 define `clinica.contacto.telefono` y `clinica.contacto.horario` con otra forma y un teléfono de relleno (`753 000 0000`). Al fusionar habría dos fuentes del contacto con datos distintos. | Decisión del usuario (2026-10-28): la 041 usa la forma de la 038 (`clinica.contacto.telefono`, `clinica.contacto.horario`) con el teléfono real (+52 753 162 5220), y la 038 adopta ese valor al retomarse. `/plan 041 --fix A27` (D14, D16) y `/tasks 041 --fix A27` (T006 y las tareas que leen el contacto). Test: el teléfono del correo y del talón es el de `clinica.contacto.telefono` |
| A28 | Seguridad / Constitución (P8) | ALTA | P8 y RNF «ningún log…» (✅); D7; verificación tras el despliegue (solo `dentissa-access.log`); `docker/nginx/dentissa.prod.conf:23` (`error_log`) | En los errores de upstream (502/504), el `error_log` de nginx escribe `request:` y `referrer:` con el token sin enmascarar, y su formato no se puede configurar. P8 figura como ✅. | Decisión del usuario (2026-10-28): excepción acotada a P8. `/plan 041 --fix A28`: P8 «❌ aceptado» solo para el `error_log` en los fallos de PHP, con el motivo (token de un solo uso y 60 minutos; log solo en el servidor), el aprobador y la fecha, y como destino la rotación y la retención de logs (OB8.b, objetivo 3). La verificación tras el despliegue (Observabilidad, T095) dice qué logs revisa y anota si `dentissa-error.log` tiene tokens. `/tasks 041 --fix A28` (Constitution Check, T091, T095) |
| A29 | Ambigüedad (P9) | MEDIA | D9; T016 `…_los_valores_de_la_hoja_son_tokens`; T017 `…_acceso_css_sin_literales_fuera_de_root` | No se dice qué propiedades se revisan ni qué valores quedan exentos: `0`, `100%`, `auto`, `@media` (no admite `var()`), anchos de tabla del correo, `font-family`, `font-weight`. El test puede ser imposible o dejar pasar literales. | `/plan 041 --fix A29` y `/tasks 041 --fix A29`: el plan fija la lista de propiedades revisadas y la de valores exentos. Tests: fallan con un literal fuera de las exenciones y pasan con ellas |
| A30 | Cobertura de riesgos | BAJA | `plan.md` → TM13 («Residuo aceptado») | El plan acepta por su cuenta el residuo del historial del navegador. | Decisión del usuario (2026-10-28): residuo aceptado. `/plan 041 --fix A30` lo registra con la fecha |
| A31 | Cobertura | BAJA | D15; `design.md`; T042 («ocho páginas») | «Contraseña nueva en espera» no está entre las ocho páginas de `design.md` ni en la medición final; solo T029 la mide a 320 px. | Nota de T042: medir también «nueva en espera» con los criterios de K6 a 320, 390 y 1440 px; `/review` hace lo mismo (propuesta de aceptación) |
| A32 | Ambigüedad (tests) | BAJA | T010 `…_con_el_interruptor_apagado_no_hay_rutas_ni_enlace` | Recrear la aplicación con `DatabaseTransactions` deja la transacción en la conexión vieja, y la variable de `phpunit.xml` tiene prioridad sobre `config()`. | Nota de T010: el test apaga el interruptor en el entorno antes de recrear la aplicación y lo restaura después; la suite completa pasa en cualquier orden (propuesta de aceptación) |

Sin cambio estructural: el delta usa los módulos previstos y solo suma claves de configuración,
cinco tokens adelantados de la 038, un estado de pantalla y dos amenazas.

## Decisiones del usuario
Decididas el 2026-10-28 (ronda 1, ya aplicadas): A1/A2, A7, A13, A17.

Decididas el 2026-10-28 (ronda 2):
- **A28** → excepción acotada a P8 para el `error_log` de nginx en los fallos de PHP, con destino
  en OB8.b.
- **A27** → contacto con la forma de la 038 (`clinica.contacto.*`) y el teléfono real.
- **A30** → se acepta el residuo de TM13 (historial del navegador, un solo uso y 60 minutos).

## Aceptados
Aceptados por el usuario el 2026-10-28 (ronda 1):
- **A19** → nota de T031, T032: fijar el orden de `SesionVigente` respecto a `ContextoActor` de la 020 en el grupo `web` y comprobar que los dos listeners de `Login` conviven; los tests de correlación de la 020 siguen en verde.
- **A20** → nota de T003: anotar la fecha de publicación de `@fontsource-variable/roboto` 5.2.5 (antigüedad mínima de 7 días).
- **A22** → nota de T029: la contraseña nueva usa `Password::min(8)`, la misma regla que el registro y el perfil; lo comprueba `test_valida_cada_campo`.
- **A23** → spec: las incoherencias de redacción de la spec (presupuesto 5 frente a 7, «K1–K11» frente a K12) se anotan en su Historial en `/release`; las del plan van en `/plan 041 --fix A23`.

Aceptados por el usuario el 2026-10-28 (ronda 2):
- **A31** → nota de T042: medir también el estado «contraseña nueva en espera» con los criterios de K6 a 320, 390 y 1440 px; `/review` hace lo mismo.
- **A32** → nota de T010: el test del interruptor apagado lo apaga en el entorno antes de recrear la aplicación y lo restaura después, sin dejar la transacción en la conexión vieja; la suite completa pasa en cualquier orden.
