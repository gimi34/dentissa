---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-10-25
spec_sha: 7ba3fdfb034e
plan_sha: b555efe0c963
tasks_sha: 2e1f58a770a8
---

# Análisis · 032 Alertas y copias cifradas en producción

## Resumen
Resultado **fail**, por dos hallazgos críticos:
- **A1:** la vuelta atrás con copia de D4 no podría restaurar, porque la primera llamada a
  `restaurar.sh` borra la clave antes de la segunda.
- **A4:** P8 aparece como ✅ aunque los logs nuevos no llevan `X-Request-Id`.

Hay además cuatro altos:
- A2 y A3: `restaurar.sh` desde un temporal y la verificación interna del primer despliegue;
- A5 y A6: la clave y la contraseña de Gmail.

El usuario decidió el 2026-10-25 cuatro cuestiones (sección Decisiones del usuario). La de A5
cambia D3 y D4: la clave secreta ya no pasa por el servidor. Todo se corrige con `--fix` del plan y
de las tareas; la spec no cambia.

Análisis completo hecho por un subagente independiente (perfil `critical`, `opus`); cada
hallazgo crítico y alto se comprobó en el archivo citado.

Conteo: CRÍTICA 2 · ALTA 4 · MEDIA 14 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 12 / 12 (faltan amenazas: A5, A6, A7) |
| Principios de la constitución evaluados | 10 / 10 (P8 viola sin excepción: A4) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | CRÍTICA | plan Contratos (`restaurar.sh`), D3, D4; T029, T030 | `volver.sh` llama dos veces a `restaurar.sh` (comprobar y restaurar). El `trap EXIT` de la primera borra la clave de `/dev/shm` y mata el agente, así que la segunda no puede descifrar: la vuelta con copia (CA9, TM11) falla siempre. | Con la decisión de A5, la clave no está en el servidor. Requisito: la vuelta con copia completa comprueba y restaura con una sola introducción de la frase en el PC. Test: un caso en T017 sobre el flujo de `volver.sh`, y T044 `volver.sh v0.10.0` con copia hasta el final. `/plan --fix` |
| A2 | Inconsistencia | ALTA | D4, Rollback; `deploy/restaurar.sh:27`, `deploy/volver.sh:61` | `restaurar.sh` localiza el clon a partir de su propia ruta. Copiado a un temporal o sacado con `git show` a `/tmp`, no encuentra `docker-compose.prod.yml`. | Requisito: la restauración funciona con el script en cualquier ruta. Test: caso en T016/T017 sin `dirname "$0"` y restauración desde un temporal en T044. `/plan --fix` |
| A3 | Inconsistencia | ALTA | D12, Rollout (D6), T041, T097; `deploy/desplegar.sh:107` | La sección «Avisos y copias» (temporizador activo, sin `.sql.gz`) corre también con `--interna`. `desplegar.sh` la ejecuta antes de `preparar-vigilancia.sh` y de `cifrar-copias-antiguas.sh`, así que el primer despliegue falla y propone volver: contradice 030 CA1. | Requisito: el despliegue de la 032 pasa su verificación interna y la sección se comprueba completa al final del Rollout. Test: caso en T019, y en T041 `desplegar.sh` sale con 0. `/plan --fix` |
| A4 | Constitución | CRÍTICA | plan Constitution Check P8, Observabilidad; tasks Constitution Check P8 | El canal `seguridad` añade logs por petición sin `X-Request-Id`, que P8 exige. Está marcado ✅ sin excepción aceptada. | P8 → «❌ aceptado» con aprobación del usuario (2026-10-25) y vencimiento al liberar la 020, que añade el identificador también a este canal. Comprobación: `/review` encuentra la fila con fuente usuario. `/plan --fix`, `/tasks --fix` |
| A5 | Seguridad | ALTA | D3; Modelo de amenazas (TM4); spec CA8 y § Seguridad | Con D3, la clave secreta y su frase pasan por el servidor en cada restauración. Un atacante que ya esté dentro las captura y lee todas las copias. CA8 dice que lo que abre las copias no está en el servidor. | Decisión del usuario: descifrar en el PC. Requisito: la clave secreta y su frase nunca llegan al servidor; el servidor solo recibe el volcado ya comprobado. Nueva TM con su control. Test: casos de texto en T016/T017 (sin `--clave` ni `gpg --import` en los scripts del servidor) y T044 (`gpg --list-secret-keys` vacío en el servidor durante y después). `/plan --fix`, `/tasks --fix` |
| A6 | Seguridad | ALTA | plan Contratos (`MAIL_PASSWORD`), D9, Modelo de amenazas | La contraseña de aplicación de Gmail da acceso a todo el buzón. En `app.env` y en el contenedor `app`, quien comprometa `app` leería el correo personal del responsable. | Decisión del usuario: una cuenta de Gmail solo para enviar, sin otros datos. Nueva TM con su control. Comprobación: nota de T040 (cuenta dedicada, sin valores) y guía (T034). `/plan --fix`, `/tasks --fix` |
| A7 | Seguridad | MEDIA | D10, P4, Modelo de amenazas; `docs/observability.md` OB8 | El log de acceso guarda búsquedas con datos personales (OB8) y montarlo en `app` amplía quién las lee. `$remote_user` y la petición admiten espacios y corchetes: una expresión mal anclada puede ocultar o inventar un 5xx. | Dos amenazas nuevas. Test: en VigilanteTest, líneas con `[fecha] "GET /" 200` dentro del usuario o de la petición; el código leído es el real. `/plan --fix` |
| A8 | Seguridad | MEDIA | D2, T015, T028 | En `… \| tee >(tail -n 1 > marca) \| gzip \| gpg`, el script no puede esperar la sustitución de procesos. La marca puede leerse incompleta y dar falsas «copia incompleta». | Requisito: la marca está completa antes de comprobarla. El test comprueba esa garantía, no el texto literal. Comprobación: varias copias seguidas sin falsos fallos en T041. `/plan --fix` |
| A9 | Ambigüedad | MEDIA | D8, Contratos (Estado), CA4, T012, T024 | La ventana de 5 min se evalúa cada minuto. No se define qué suma el «recuento acumulado» ni se marcan los fallos ya contados, que se contarían varias veces. | Requisito: el recuento son eventos distintos desde el último envío. Test: 11 fallos en un minuto y 10 ejecuciones → recuento 11. `/plan --fix` |
| A10 | Ambigüedad | MEDIA | D8, T003, TM12 | El tope de bytes por ejecución no tiene valor. Con estado vacío y un log sin rotar, el vigilante empieza en el byte 0; con más tráfico que el tope, se queda atrás e incumple los 10 min de CA1. | Fijar el valor y el punto de partida. Tests: primera ejecución sobre un log grande y tráfico mayor que el tope. `/plan --fix` |
| A11 | Inconsistencia | MEDIA | Contratos (línea de `seguridad`), D7, T010, T020 | `JsonFormatter` escribe `{"message","context","level","channel","datetime","extra"}`, no la línea plana del contrato. La 020 añadirá campos en `extra`. | El contrato describe la forma real de la línea. T010 lo comprueba y VigilanteTest lee líneas con campos extra. `/plan --fix` |
| A12 | Inconsistencia | MEDIA | D7, Contratos (`cuenta`); `AuthController.php:35` (privada), `:85` (`Lockout($request)`), `:95` (`activo => 1`) | El listener no puede llamar a `claveLimiteLogin`, que es privada. `Lockout` solo trae la petición. Con una cuenta inactiva, `Failed` llega con usuario `null` mientras el limitador usa `cuenta:<id>`. `AuthController` no está en el plan. | El plan dice dónde vive la regla común y lista el archivo en la tarea. Tests en T010: cuenta inactiva, y `Lockout` con la misma huella que su `Failed`. `/plan --fix`, `/tasks --fix` |
| A13 | Cobertura | MEDIA | CA9 («dice por qué»), T016, T029, T044 | Nada comprueba que el mensaje distinga clave ajena, copia alterada y copia cortada: gpg sale con 2 en los tres casos. | Tres mensajes distintos. Test: caso en T016; la nota de T044 recoge el mensaje de cada rechazo. `/tasks --fix` |
| A14 | Ambigüedad | MEDIA | T044, CA9; `.env.example` (`CACHE_STORE=database`) | El «antes» de la comparación no está definido. Con producción abierta, `cache` cambia y `CHECKSUM TABLE` no puede coincidir. Restaurar una copia antigua borraría datos nuevos. | Foto con `app` y `web` parados, copia tomada justo después y comparación contra esa foto. `/plan --fix`, `/tasks --fix` |
| A15 | Ambigüedad | MEDIA | T041, CA7, D6 | Con D6 el checkout ya se hizo a mano, así que «el `HEAD` no cambia» siempre se cumple. En el primer despliegue, el checkout ocurre antes de saber si hay con qué cifrar. | Requisito: en el primer despliegue, la clave pública y gpg se comprueban antes del checkout. T041 ensaya con una ref distinta del `HEAD`. `/plan --fix`, `/tasks --fix` |
| A16 | Cobertura | MEDIA | CA10, D5, T031, T097 | Una copia en claro dañada se deja para que el responsable decida, y entonces CA10 no se cumple. La búsqueda no mira la carpeta personal (ensayos de la 030). | Definir el estado final de una copia dañada y las rutas que se revisan. Comprobación: `verificar.sh` y T097 sin copias en claro. `/plan --fix` |
| A17 | Inconsistencia | MEDIA | D4, T030, Contratos; `deploy/desplegar.sh:120` | No se define la nueva forma de uso de `volver.sh` con la copia cifrada. La orden de vuelta que imprime `desplegar.sh` no se alinea, y `desplegar.sh` no está entre los cambios. | Contrato de `volver.sh` con el flujo desde el PC (A5) y orden de `desplegar.sh` alineada. Test: caso en ConfiguracionProduccionTest. `/plan --fix`, `/tasks --fix` |
| A18 | Seguridad | MEDIA | T014, T026; `.dockerignore` (precedente `**/*.env`) | En `.dockerignore`, `*.asc` y `*.gpg` solo excluyen la raíz del contexto. | Exclusión a cualquier profundidad. Test: T014 comprueba `**/*.asc` y `**/*.gpg`. `/tasks --fix` |
| A19 | Ambigüedad | MEDIA | D14, CA8 (copia robada del PC) | En el PC están juntas la copia y la clave secreta: CA8 depende solo de la frase, sin mínimo fijado. | Mínimo de la frase en la guía. Comprobación: la nota de T040 dice que se cumple, sin revelarla. `/plan --fix` |
| A20 | Duplicación | MEDIA | D11; plan de la 020 (`scheduler` con `schedule:work`) | D11 descarta el planificador de Laravel, pero la 020 añadirá uno: habrá dos mecanismos de programación. | Registrar en D11 cómo convergen al llegar la 020 (o mandarlo al roadmap). `/plan --fix` |
| A21 | Cobertura | BAJA | TM10, T025 | Nadie prueba que `dentissa:vigilar` salga con código distinto de 0 cuando falla el envío. | Caso en el test del comando. `/tasks --fix` |
| A22 | Inconsistencia | BAJA | plan D1 «(proposed…)»; ADR 0007 `accepted` | El estado del ADR no coincide entre el plan y el ADR. | Alinear. `/plan --fix` |
| A23 | Inconsistencia | BAJA | plan Impacto en arquitectura; `docs/architecture.md` («No hay correo…»); `docs/security.md` («ni tareas programadas»); T035, T090 | Las secciones que nombra el plan no existen en `architecture.md`, y faltan «Integraciones externas» y la superficie de `security.md`. | T090 y T035 enumeran las secciones reales. `/plan --fix`, `/tasks --fix` |
| A24 | Inconsistencia | BAJA | spec 030 (Fuera de alcance «Envío de correos»; Cobertura RS15.b y OB6.b → 031); spec 001 CA9; T098 | T098 solo anota 030 CA6. | T098 también actualiza esos destinos de la 030 y la anotación de 001 CA9 (el canal `seguridad` no es auditoría). `/tasks --fix` |
| A25 | Duplicación | BAJA | D15; plan de la 038 (Rollout `volver.sh v0.10.0`) | Con la 032 liberada, la vuelta de la 038 a v0.10.0 cruzaría la 032. | D15 lo anota para la 038. `/plan --fix` |
| A26 | Ambigüedad | BAJA | spec RNF («no hace más lenta… de forma apreciable»); plan Riesgos (falsos avisos) | El RNF no tiene métrica y el riesgo de falsos avisos se acepta sin fuente usuario. | Riesgo aceptado por el usuario (2026-10-25). El RNF se verifica a mano, declarado así en la Trazabilidad. `/plan --fix` |
| A27 | Seguridad | BAJA | TM8, P8; Contratos (HMAC con `APP_KEY`) | Con la `APP_KEY` del servidor, una IPv4 se recupera por fuerza bruta y `cuenta:<id>` enumerando ids. Es seudonimización, y se reutiliza la clave de cifrado de la aplicación. | Documentarlo así en TM8 o usar una clave derivada. `/plan --fix` |
| A28 | Cobertura | BAJA | D12, CA6 | `MAIL_FROM_ADDRESS` no se comprueba: con el texto de ejemplo de la guía, `verificar.sh` da OK y el envío falla. | Incluirla en las comprobaciones de correo y en T019. `/plan --fix`, `/tasks --fix` |

## Decisiones del usuario
Decididas por el usuario el 2026-10-25:
- **A5:** descifrar en el PC. La clave secreta y su frase no salen del PC: la comprobación completa
  se hace en el PC y el servidor solo recibe el volcado por SSH. Sustituye a D3 (decisión del
  2026-10-23) y cambia D4.
- **A6:** una cuenta de Gmail solo para enviar los avisos, sin otros datos, con su contraseña de
  aplicación. Los avisos llegan al correo de siempre del responsable.
- **A4:** P8 como «❌ aceptado», con vencimiento al liberar la 020.
- **A26:** se acepta el riesgo de falsos avisos con poco tráfico (umbral de la spec sin mínimo de
  peticiones); si molesta, se ajusta en otra spec.

## Aceptados
Ninguno: todos los hallazgos se corrigen en la vuelta de `/plan --fix` y `/tasks --fix`.
