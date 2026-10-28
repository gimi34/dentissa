---
result: pass
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-10-28
spec_sha: 8456cad72e93
plan_sha: dbd1df3d8810
tasks_sha: eb8943d831b7
---

# Análisis · 041 Recuperar contraseña

## Resumen
Resultado **pass**.
- Los siete hallazgos abiertos de la ronda 2 (A24–A30) quedan resueltos con `/plan --fix` y
  `/tasks --fix`, incluido el alto A28 (excepción acotada a P8, aprobada por el usuario).
- El delta deja tres hallazgos bajos (A33–A35). El usuario los acepta como notas de sus tareas. No
  hay cambio estructural.
- Delta de 236 líneas (70 KB, varias solo de fin de línea), revisado por un subagente
  independiente (perfil `standard`, `sonnet`).
- Abiertos sin aceptar: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0. Aceptados en total: 11.
- Validador: 0 errores. Avisos: el plan y las tareas citan OB4, OB8 y SE2, que la spec no cita;
  son decisiones del usuario de estas rondas (SE2.a dentro, OB4.a y OB8.b como destino de
  residuos).

Ronda anterior: [history/analysis.r2.md](history/analysis.r2.md).

Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 14 / 14 |
| Principios de la constitución evaluados | 10 / 10 (los afectados por el delta: P8 ❌ aceptado y bien formado; P9 ✅ con D17) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A24 | MEDIA | resuelto | Estrategia de pruebas, D15, TM2 y CA9 dicen «todo `POST` gasta intento»; `…_los_errores_de_campo_gastan_intentos` en T012, que T029 hace pasar |
| A25 | MEDIA | resuelto (efecto menor → A34) | D15 y T028: solo usa un token alfanumérico de hasta 255 caracteres y una cuenta UUID; si no, «pedir» en espera; `…_la_espera_con_entrada_malformada_no_falla` en T012 |
| A26 | MEDIA | resuelto (efecto menor → A33) | Contrato de `POST /contrasena/nueva` y T029: un error de campo vuelve a `contrasena.nueva` sin `back()`; `…_el_error_de_campo_vuelve_a_la_url_del_token_sin_referer` en T011 y en la fila CA4 |
| A27 | MEDIA | resuelto | D14, D16 y la tabla de módulos usan `clinica.contacto.*`, con la forma de la 038 (comprobado con `git show feature/038-pagina-inicio:config/clinica.php`); T006, T016, T021, T022, T027 y T029 leen `config('clinica.contacto')` |
| A28 | ALTA | resuelto | P8 «❌ aceptado: motivo — aprobado por el usuario el 2026-10-28» en el plan y en las tareas, acotado al `error_log` en los fallos de PHP; destino OB8.b (`docs/observability.md`); verificación en Observabilidad, RNF, T091 y T095 |
| A29 | MEDIA | resuelto (efecto menor → A35) | D17 fija las propiedades revisadas y las exenciones; T016 y T017 la citan |
| A30 | BAJA | resuelto | TM13: «Residuo aceptado por el usuario el 2026-10-28» |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A33 | Seguridad / ambigüedad | BAJA | Contrato de `POST /contrasena/nueva`; T029; T011 `…_valida_cada_campo` | No se dice qué pasa si el error de validación es del propio `token` o de la `cuenta` (faltan, son un array o la cuenta no es UUID): redirigir a `contrasena.nueva` con esos valores podría dar un 500 o una URL inválida. | Nota de T029 y T011: mismo criterio de formato que D15. Si el token o la cuenta no son válidos, «Este enlace ya no sirve», sin redirigir a `contrasena.nueva`. Caso en `test_valida_cada_campo` |
| A34 | Seguridad | BAJA | D15, T028; T012 `…_la_espera_con_entrada_malformada_no_falla` | En «pedir», la respuesta del limitador conserva el correo antes de validar. Con `email[]` o un valor enorme, `old('email')` puede romper la vista. | Nota de T028 y T012: «pedir» conserva el correo solo si es texto de hasta 255 caracteres. El test incluye `email[]` y un correo enorme |
| A35 | Ambigüedad (P9) | BAJA | D17; T024 (`@font-face` de Roboto); T017 `…_acceso_css_sin_literales_fuera_de_root` | D17 no exime el bloque `@font-face`, cuyos descriptores (`font-family`, `font-weight` en rango, `font-display`, `src: url()`) son literales inevitables: el test fallaría con el propio `acceso.css`. | Nota de T017 y T024: el test excluye los descriptores de `@font-face`. Además, → plan: añadir esa exención a D17 en la próxima corrección del plan |

Observación de redacción, sin hallazgo: D17 quedó entre D9 y D10 en el plan.

## Decisiones del usuario
Ronda 1 (2026-10-28): A1/A2 (041 independiente de la 038), A7 (los correos copian los tokens, con test), A13 (residuos del mailer local y de OB4.a), A17 (SE2.a entra).

Ronda 2 (2026-10-28): A27 (contacto con la forma de la 038 y el teléfono real), A28 (excepción acotada a P8 para el `error_log`, destino OB8.b), A30 (residuo de TM13 aceptado).

## Aceptados
Aceptados por el usuario el 2026-10-28 (ronda 1):
- **A19** → nota de T031, T032: fijar el orden de `SesionVigente` respecto a `ContextoActor` de la 020 en el grupo `web` y comprobar que los dos listeners de `Login` conviven; los tests de correlación de la 020 siguen en verde.
- **A20** → nota de T003: anotar la fecha de publicación de `@fontsource-variable/roboto` 5.2.5 (antigüedad mínima de 7 días).
- **A22** → nota de T029: la contraseña nueva usa `Password::min(8)`, la misma regla que el registro y el perfil; lo comprueba `test_valida_cada_campo`.
- **A23** → spec: las incoherencias de redacción de la spec (presupuesto 5 frente a 7, «K1–K11» frente a K12) se anotan en su Historial en `/release`; las del plan van en `/plan 041 --fix A23`.

Aceptados por el usuario el 2026-10-28 (ronda 2):
- **A31** → nota de T042: medir también el estado «contraseña nueva en espera» con los criterios de K6 a 320, 390 y 1440 px; `/review` hace lo mismo.
- **A32** → nota de T010: el test del interruptor apagado lo apaga en el entorno antes de recrear la aplicación y lo restaura después, sin dejar la transacción en la conexión vieja; la suite completa pasa en cualquier orden.

Aceptados por el usuario el 2026-10-28 (ronda 3):
- **A33** → nota de T029, T011: si el `token` o la `cuenta` no tienen formato válido (faltan, array, cuenta no UUID), «Este enlace ya no sirve» sin redirigir a `contrasena.nueva`; un caso en `test_valida_cada_campo` lo comprueba.
- **A34** → nota de T028, T012: «pedir» conserva el correo solo si es texto de hasta 255 caracteres; `…_la_espera_con_entrada_malformada_no_falla` incluye `email[]` y un correo enorme.
- **A35** → nota de T017, T024: `…_acceso_css_sin_literales_fuera_de_root` excluye los descriptores del bloque `@font-face` (`font-family`, `font-weight`, `font-display`, `src`).
