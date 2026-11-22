---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-21
spec_sha: 6634b6321aaf
plan_sha: 6f6d5b917912
tasks_sha: f182b21b7a42
---

# Análisis · 002 Autorregistro de pacientes

## Resumen
`fail`: no hay nada crítico, pero hay cuatro hallazgos altos.
- Las roturas de CA2 no muerden con seguridad (A1).
- P9 no se verifica: faltan el foco de los enlaces y la medida del contraste (A2).
- Lo que queda de RS13 no tiene ID (A3).
- La excepción P7 solo consta en el plan (A4).

Lo que el plan da por hecho del código se cumple:
- `DB::beforeExecuting` existe.
- La ruta `contrasena.solicitud` existe.
- `RECUPERAR_CONTRASENA=true` en `phpunit.xml`.
- La sesión `google_registro` existe.
- El limitador `registro` es de 5 por minuto.

Análisis independiente con un subagente de contexto limpio. El perfil `critical` usa `opus`, pero ese modelo agotó su límite de uso hasta las 17:20, así que se lanzó con `sonnet` (contrato → «Modelo de cada subagente»). Cada hallazgo se comprobó después en su archivo.

Conteo: CRÍTICA 0 · ALTA 4 · MEDIA 6 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8. CA1–CA7 ya se cumplen, con test de caracterización o existente; CA8 con test que falla antes. Las roturas de CA2 quedan pendientes de A1. |
| Amenazas con control y test | 3 / 3 dentro; TM2 fuera, con destino sin ID (A3) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia (código) | ALTA | `tasks.md` T011; `RegisterController::asignarDentistaAutomatico` | La consulta agrupa por `personal.id, personal.creado_en` y ordena por `COUNT` y `creado_en`. Sin el `orderBy` de `creado_en`, MariaDB resuelve el empate en un orden no definido, en la práctica el del UUID. Con UUID aleatorios, la rotura de T011 falla o no según el azar. La rotura de `activo` solo muerde si el inactivo gana al activo en el desempate. Y no hay rotura del conteo, que es el criterio principal. | `/tasks 002 --fix A1`: cada rotura de CA2 debe hacer fallar un test concreto de forma determinista. Para eso, el test fija los `id` y los `creado_en` de los dentistas: el más antiguo con el UUID mayor, y el inactivo más antiguo que el activo. Las tres roturas (conteo, antigüedad y activo) quedan anotadas en T011. |
| A2 | Constitución (P9) | ALTA | `plan.md` P9, D3; `tasks.md` T015, T021; `auth.css:270, 372-431` | P9 exige foco visible y contraste de al menos 4,5:1. Los enlaces nuevos dentro de `.error-msg` no tienen regla `:focus-visible`: `auth.css` solo la define para otros elementos y quita el `outline` de los campos. El contraste de `--red` se afirma, pero ningún test lo mide sobre el fondo real (blanco). | `/plan 002 --fix A2` y `/tasks 002 --fix A2`. Los enlaces del rechazo deben tener un foco visible con `--focus-ring*`, y el contraste de `--red` sobre su fondo debe ser de al menos 4,5:1. Se comprueba con un test sobre `auth.css` que encuentre la regla de foco con los tokens y calcule el contraste con los valores de los tokens. |
| A3 | Cobertura de riesgos | ALTA | `spec.md` «Cobertura de riesgos»; `plan.md` Impacto; `docs/security.md` RS13 | El plan dice que RS13.b queda «mitigada en parte», un estado que `security.md` no usa. Lo que queda (TM2: el alta entra o no al panel) se manda a una «spec futura» sin ID. RS13.b no se podría cerrar y el residuo no se puede rastrear. | `/specify --edit 002` (decisión del usuario, 2026-11-21): RS13.b se queda con el mensaje (dentro, CA8) y se crea RS13.c, no distinguir una cuenta nueva de una existente, fuera y con destino una spec futura de confirmación del correo. Después, `/plan 002 --fix A3` en Impacto. Se comprueba con `aidd.py validate`. |
| A4 | Constitución (P7) | ALTA | `spec.md` Decisiones; `plan.md` P7, D6 | La excepción P7 (el alta sin auditoría) está solo en el plan y en las tareas. Falta en las Decisiones de la spec. | `/specify --edit 002` (decisión del usuario, 2026-11-21): fila `contradicción` con fuente `usuario`. Se comprueba con `aidd.py validate`. |
| A5 | Inconsistencia | MEDIA | `spec.md` CA8; `plan.md` Contratos; `register.blade.php` | CA8 dice que se conservan los datos escritos salvo la contraseña. Hoy la casilla de términos vuelve desmarcada, y T014 solo comprueba el nombre y el apellido. | Decisión del usuario (2026-11-21): la casilla vuelve desmarcada, porque el consentimiento se da en cada envío. `/specify --edit` lo precisa en CA8 y `/plan --fix A5` en Contratos. Con `/tasks --fix A5`, T014 comprueba el teléfono, la fecha, el género, la dirección y la ciudad, y también que la casilla vuelve sin marcar. |
| A6 | Constitución (P10) | MEDIA | `plan.md` P10; `docs/seo.md` SE1 | P10 aparece como ✅ sin nombrar la deuda previa de `/registro`: no tiene descripción ni canónica (SE1.a y SE1.b). | `/plan 002 --fix A6`: P10 como «✅ sin cambios en el `GET`; deuda previa SE1 → `docs/seo.md`». |
| A7 | Ambigüedad | MEDIA | `spec.md` Supuestos y CA2 | Que se ignoren los dentistas inactivos es solo un supuesto. Tampoco se dice qué pasa si no hay ningún dentista activo. | `/specify --edit 002` (decisión del usuario, 2026-11-21): el supuesto pasa a CA2, junto con el caso «sin dentistas activos, el alta se completa sin dentista». `/tasks --fix A7` añade un test de caracterización en T011. |
| A8 | Inconsistencia | MEDIA | `tasks.md` T010 | Con `DB::beforeExecuting`, la excepción sube sin envolver y el manejador la convierte en 500, pero la tarea no dice qué se aserta. | Aceptado → nota de T010. |
| A9 | Inconsistencia | MEDIA | `tasks.md` T010–T015 | Los payloads válidos tienen que llevar `password_confirmation` y `terminos`, y la ciudad, si se envía, tiene que ser de la lista. Cada test debe asertar la clave de error concreta. | Aceptado → nota de T010. |
| A10 | Inconsistencia | MEDIA | `tasks.md` T014, T015; `register.blade.php:63-72` | El aviso general repite el mensaje neutro. Si los enlaces se buscan en toda la página, la aserción podría pasar por accidente. | Aceptado → nota de T014 y T015. |
| A11 | Cobertura | BAJA | `tasks.md` T015; `AppServiceProvider.php:60-64` | El límite de la 030 deja su propio error en `email`, que no es la constante, así que no lleva enlaces. Es lo correcto, pero no tiene test. | Aceptado → nota de T015. |
| A12 | Cobertura | BAJA | `tasks.md` T013 | El umbral de la contraseña solo se prueba con 7 caracteres. | Aceptado → nota de T013. |
| A13 | Seguridad | BAJA | `plan.md` TM4; `tasks.md` T014 | El correo vuelve con `old('email')` y Blade lo escapa, pero no hay comprobación. | Aceptado → nota de T014. |
| A14 | Inconsistencia | BAJA | `tasks.md` T011, T012 | T011 no dice cómo se crean los dentistas y pacientes del escenario. T012 no dice qué claves necesita `google_registro`. | Aceptado → nota de T011 y T012. |
| A15 | Duplicación y conflicto | BAJA | `plan.md` | La 054 toca `GoogleAuthController` y quizá la obligatoriedad del correo. La 014 usa `pacientes.dentista_id`, que viene de la asignación de CA2. Sin conflicto de código. | Aceptado → plan: una línea de coordinación en Riesgos. |
| A16 | Cobertura | BAJA | `tasks.md` T095 | No dice qué cuenta de prueba se usa en producción. | Aceptado → nota de T095. |

## Decisiones pendientes del usuario
Resueltas el 2026-11-21:
- A3, A4 y A7: `/specify --edit 002` con los tres cambios (recomendada).
- A7: el caso sin dentistas activos se fija con un test de caracterización (recomendada).
- A5: la casilla de términos vuelve desmarcada (recomendada).

## Aceptados
Aceptados para resolver en `/implement` el 2026-11-21:
- **A8** → nota de T010: el test de la transacción aserta el 500 y que no queda ningún `usuarios` con ese correo (`assertDatabaseMissing`); el callback compara el SQL con ``insert into `pacientes` ``
- **A9** → nota de T010: un payload base compartido, con `password_confirmation` y `terminos`, y sin ciudad o con una de la lista; cada test aserta la clave de error concreta
- **A10** → nota de T014, T015: los enlaces y el `aria-*` se buscan dentro del grupo del campo del correo (xpath), no en toda la página
- **A11** → nota de T015: con el límite agotado (sexto intento), el error del correo es el del límite, sin los enlaces, y vuelven los datos
- **A12** → nota de T013: una contraseña de 8 caracteres se acepta (umbral exacto)
- **A13** → nota de T014: un correo con `<` vuelve escapado en el campo
- **A14** → nota de T011, T012: reutilizar los creadores de `tests/Feature/Concerns` o los del archivo; `google_registro` con `google_id`, `nombre`, `apellido`, `email` (nuevo, `@example.test`) y `foto_url` opcional, como `CiudadListaCerradaTest`
- **A15** → plan: línea de coordinación con la 054 (`GoogleAuthController`; si el correo deja de ser obligatorio, revisar la regla `unique` y CA8) y con la 014 (la asignación de CA2 es la base del alcance)
- **A16** → nota de T095: una cuenta de prueba ya existente, nombrada sin escribir su correo en ningún documento
