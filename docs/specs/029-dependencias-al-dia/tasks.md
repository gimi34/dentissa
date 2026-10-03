---
spec: 029-dependencias-al-dia
plan: plan.md
status: approved
impl_base: cc1bb85
---

# Tareas · 029 Dependencias sin avisos de seguridad conocidos

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.

## Constitution Check
| Principio | Resultado | Justificación | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan 029 `approved`; estas tareas se aprueban y se analizan antes de `/implement`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010 amplía `DependenciasSegurasTest` antes de actualizar (falla hoy) y T020 depende de él. CA2 lo cubre el test del frontend de la 024. CA3–CA7 no describen comportamiento: sus comprobaciones (T021, T022, T023, T040, T091 y la release) se anotan en su tarea (constitución 1.2.1). | test: T010 (fallo previo anotado) · manual: notas de T021–T023 |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas ni permisos. | — |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas ni SQL. | — |
| P5 Nada sensible en el repo | ✅ | Solo cambian `composer.lock`, un test y la documentación. | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | T020 corrige los tres avisos; T040 exige 0 avisos de cualquier severidad. | lint: `composer audit --locked` sin filtro y `npm audit` en T020 y T040 |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. | — |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. | — |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Ninguna tarea toca la interfaz. | — |

## Preparación
- [x] T001 Registrar la línea base en `feature/029-dependencias-al-dia`: commit de partida (para comparar `composer.lock` en T021), recuento de `php artisan test`, salida de `composer audit --locked` sin filtro (2 bajas en `laravel/framework` y `league/flysystem`, 1 media en `phpseclib/phpseclib`), `npm audit` y las versiones de las tres bibliotecas en `composer.lock` — docs/specs/029-dependencias-al-dia/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
  - nota: línea base del 2026-10-03: commit de partida `cc1bb85` (`impl_base`); `php artisan test` → 152 en verde (1207 aserciones); `composer audit --locked` sin filtro → `laravel/framework` (baja), `league/flysystem` (baja) y `phpseclib/phpseclib` (media); `npm audit` → 0; versiones en `composer.lock`: `laravel/framework` v12.62.0, `league/flysystem` 3.35.1, `phpseclib/phpseclib` 3.0.55.
- [x] T002 Repetir la simulación el día de implementar (D1, D2): `docker compose exec app composer update laravel/framework league/flysystem phpseclib/phpseclib --with-dependencies --dry-run`, consultar en Packagist (`https://repo.packagist.org/p2/<paquete>.json`, campos `time` y `source`) cada versión que cambia, y fijar con `paquete:versión` cada una que tenga menos de 7 días a la más nueva que los cumpla; volver a simular con esas fijaciones — docs/specs/029-dependencias-al-dia/tasks.md — hecho cuando: la nota recoge el comando final y la lista de versiones que cambian, todas con ≥ 7 días, de GitHub, menores o de parche, sin paquetes que aparezcan o desaparezcan y con las tres bibliotecas en versión corregida (si algo de eso no se cumple, la tarea queda con `bloqueo:` → `/plan 029 --fix`) — depende: T001
  - nota: simulación del 2026-10-03 20:43 UTC sin fijar: 41 actualizaciones; 6 con menos de 7×24 h (`doctrine/lexer` 3.0.3, `laravel/framework` v12.69.3, `monolog/monolog` 3.12.1, `nesbot/carbon` 3.14.2, `symfony/http-kernel` v7.4.20, `symfony/routing` v7.4.20).
  - nota: comando final: `composer update laravel/framework league/flysystem phpseclib/phpseclib doctrine/lexer:3.0.2 laravel/framework:12.69.2 monolog/monolog:3.12.0 nesbot/carbon:3.14.0 symfony/http-kernel:7.4.19 symfony/routing:7.4.18 --with-dependencies` (en el contenedor `app`).
  - nota: resultado de la simulación con fijaciones: 41 actualizaciones, 0 instalaciones y 0 retiradas; mismos paquetes y mismas versiones que el plan; ningún salto de versión mayor; las tres bibliotecas corregidas (`laravel/framework` v12.69.2, `league/flysystem` 3.36.0, `phpseclib/phpseclib` 3.0.57); `composer.lock` sin tocar.
  - nota: A11 — regla aplicada: al menos 7×24 h entre el `time` de Packagist (UTC) y el momento de la simulación (2026-10-03 20:43 UTC; corte 2026-09-26 20:43 UTC). `nesbot/carbon` 3.14.1 (2026-09-27) no la cumple por horas y se fija 3.14.0. T021 vuelve a medirla contra la hora de T020.

## Tests (antes de implementar)
- [x] T010 Ampliar `DependenciasSegurasTest`: `MINIMAS_SERVIDOR` gana `laravel/framework` [12 => 12.69.0], `league/flysystem` [3 => 3.35.3] y `phpseclib/phpseclib` [3 => 3.0.57], y el docblock cita la spec 029 y los avisos del 2026-10-03 — tests/Feature/DependenciasSegurasTest.php — hecho cuando: `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` falla por la razón esperada (los tres paquetes por debajo de su mínima) y el del frontend sigue en verde — cubre: CA1 — depende: T001
  - nota: falla por la razón esperada: `laravel/framework 12.62.0 < 12.69.0`, `league/flysystem 3.35.1 < 3.35.3` y `phpseclib/phpseclib 3.0.55 < 3.0.57`; el test del frontend sigue en verde; `pint --test` pasa.

## Implementación
- [x] T020 Actualizar las dependencias del servidor con el comando final de T002, sin tocar `composer.json` (controles de TM1, TM3 y TM4) — composer.lock — hecho cuando: T010 pasa, `composer audit --locked` sin filtro no reporta ningún aviso y `npm audit` reporta 0 — cubre: CA1, CA2 — depende: T002, T010
  - nota: 2026-10-03 20:45:17 UTC: el comando final de T002 escribe `composer.lock` con 41 actualizaciones (0 instalaciones, 0 retiradas); `DependenciasSegurasTest` pasa (T010); `composer audit --locked` sin filtro → "No security vulnerability advisories found"; `npm audit` → 0.
  - nota: A6 — no apareció ningún aviso nuevo: las tres bibliotecas tienen versión corregida con ≥ 7×24 h y CA1 se cumple sin excepciones (no hizo falta bloquear ni renovar EX1).
- [x] T021 Comprobar el diff de la actualización frente al commit de T001 (tests de TM1 y TM2): los paquetes que cambian en `composer.lock` son exactamente los de T002, ninguno aparece ni desaparece y ninguno cambia de versión mayor; `require` y `require-dev` de `composer.json` siguen igual; ningún paquete nuevo es `composer-plugin`; cada versión cambiada tiene en Packagist `time` con al menos 7 días el día de T020 y `source` de GitHub — (sin archivos; nota en tasks.md) — hecho cuando: la nota recoge el resultado de cada comprobación, todas en verde — cubre: CA4, CA6 — depende: T020
  - nota: comparación de `composer.lock` frente a `cc1bb85`: 41 paquetes cambiados, exactamente los de T002; ninguno aparece ni desaparece; ningún salto de versión mayor; `composer.json` sin cambios (`require`, `require-dev` y `config.allow-plugins` iguales).
  - nota: A4 — esta es la tarea de las comprobaciones de TM1 (el plan dice T022 por error).
  - nota: A5 — los 41 cambiados son dependencias, directas o transitivas, de `laravel/framework`, `league/flysystem` o `phpseclib/phpseclib` según el grafo `require` del lock anterior (0 fuera).
  - nota: A7 — en Packagist, para las 41 versiones: `source` de GitHub, y `source.reference`, `dist.url` y `dist.reference` iguales a los del lock. Limitación aceptada: el `time` de Packagist sale del commit o tag y no prueba la fecha real de publicación.
  - nota: A8 — ninguna versión cambiada es `composer-plugin` ni `composer-installer`, y ninguna cambia de `type`; `config.allow-plugins` no cambia.
  - nota: A9 — tras T020, `git status` solo muestra `composer.lock` (`vendor:publish --tag=laravel-assets`: "No publishable resources"; `package:discover` solo regenera la caché de `bootstrap/cache`, ignorada por git).
  - nota: A11 — antigüedad medida contra la hora de T020 (2026-10-03 20:45:17 UTC): la versión más reciente es `symfony/finder` v7.4.20 (`time` 2026-09-26 06:31 UTC, 7.6 días); todas ≥ 7×24 h.
- [x] T022 Ejecutar la suite completa tras la actualización (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: `php artisan test` da el recuento de T001 en verde, sin fallos nuevos (los de `DependenciasSegurasTest` incluidos) — cubre: CA3 — depende: T020
  - nota: tras T020: `php artisan test` → 152 en verde (1207 aserciones), el mismo recuento que la línea base de T001, sin fallos nuevos; los dos de `DependenciasSegurasTest` incluidos. Duración ~40 s (antes ~33 s, con variación habitual en local).
- [x] T023 Verificación manual del login con Google en local, hecha por el usuario tras T020 (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: el usuario confirma que entra con Google en `http://localhost:8000` igual que antes, y la nota recoge la fecha · verificación manual: login con Google en local tras la actualización — cubre: CA5 — depende: T020
  - nota: verificación manual OK 2026-10-03 (usuario): tras T020, el login con Google en `http://localhost:8000` funciona igual que antes.
- [ ] T024 Reformular el docblock de `DependenciasSegurasTest`: las mínimas de la 024 corrigen avisos críticos o altos (salvo `follow-redirects`, moderado) y las de la 029, avisos bajos y medios (CA1 exige cero avisos de cualquier severidad), con el párrafo ajustado a unos 85 caracteres por línea — tests/Feature/DependenciasSegurasTest.php — hecho cuando: el docblock dice eso, ninguna línea pasa de 90 caracteres, `pint --test` pasa y `DependenciasSegurasTest` sigue en verde — cubre: CA1
  - nota: añadida por /review: R1

## Integración y documentación
- [x] T040 Verificación final: `php artisan test`, `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php`, `composer audit --locked` sin filtro con 0 avisos, `npm audit` con 0, gitleaks sobre el rango de la spec sin hallazgos, `python .ai/bin/aidd.py validate docs/specs/029-dependencias-al-dia` sin errores y, por CA7, `docs/security.md` con la fila de EX1 anotada (avisos corregidos por la 029, se retira al liberarla) y RS17.a "en curso (spec 029)" — (sin archivos; nota en tasks.md) — hecho cuando: todo en verde y anotado — cubre: CA7 — depende: T021, T022, T023, T091
  - nota: 2026-10-03: `php artisan test` → 152 en verde; `pint --test` pasa en `DependenciasSegurasTest.php`; `composer audit --locked` sin filtro → "No security vulnerability advisories found"; `npm audit` → 0; gitleaks sobre `cc1bb85..HEAD` sin hallazgos; `aidd.py validate` sin errores.
  - nota: A1 — T040 no cubre CA7: lo cumple y lo comprueba T098 en la release (EX1 fuera de "Excepciones aceptadas", RS17.a mitigada con su versión, `composer audit` sin filtro sin avisos sin registrar). Aquí solo queda comprobado que no hay ningún aviso.
  - nota: A6 — sin avisos nuevos: no hizo falta ninguna excepción y CA1 se cumple tal como está escrito.
  - nota: A10 — CA2 es una comprobación declarada (P2 1.2.1, datos de un servicio externo): `npm audit` → 0 en T020 y aquí; el test del frontend de la 024 sigue en verde como apoyo.
  - nota: A13 — corrige la nota de A1: tras `/tasks --fix A1`, T040 sí comprueba CA7. Comprobado el 2026-10-03: `composer audit --locked` sin filtro sin avisos; en `docs/security.md`, la fila de EX1 dice que sus avisos quedan corregidos por la 029 y que se retira al liberarla, y RS17.a figura "en curso (spec 029)"; suite 152 en verde.
- [x] T090 Comprobar que `docs/architecture.md` no necesita cambios (no cita versiones de dependencias) y anotarlo
  - nota: `docs/architecture.md` no cita versiones menores ni de parche de las dependencias (solo "Laravel 12"), así que no cambia.
- [x] T091 Actualizar `docs/security.md`, por corrección: RS17.a → "en curso (spec 029)", y en la fila de EX1 una nota de que sus avisos quedan corregidos en la spec 029 y que la excepción se retira al liberarla. `docs/deployment.md` y `docs/observability.md` no cambian — docs/security.md — hecho cuando: los dos cambios están y ningún riesgo figura como mitigado antes de la release — cubre: CA7 — depende: T020
  - nota: RS17.a → "en curso (spec 029)"; ningún riesgo se marca como mitigado antes de la release. `docs/deployment.md` y `docs/observability.md` no cambian.
  - nota: A3 — la columna "Vence" de EX1 gana una nota: sus dos avisos quedan corregidos en la 029 y la excepción se retira al liberarla. No adelanta la retirada, que sigue en la release (T098); difiere del plan solo en esa nota.
- [ ] T092 Marcar spec como `implemented`
  - nota: spec 029 → `implemented` (2026-10-03) con CA1–CA7 marcados.
  - nota: A12 — CA7 se marca por lo que comprueba T040 (estado tras la actualización); su última frase (EX1 retirada y RS17.a mitigada) se comprueba en T098, al liberar.

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`: además, como pasos de la release (plan D6, /analyze A1), EX1 sale de "Excepciones aceptadas", RS17.a → mitigada (vX.Y.Z), el texto del riesgo de dependencias de la 024 deja de citar EX1 como vigente, `composer audit --locked` sin filtro sigue sin avisos, y el tag es anterior al 2026-10-31
  - nota: A12 — al liberar se comprueba la última frase de CA7: EX1 fuera de "Excepciones aceptadas" y RS17.a mitigada con su versión.

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T040 | T020, T024 |
| CA2 | T040 | T020 |
| CA3 | T022 | T020 |
| CA4 | T021 | T020 |
| CA5 | T023 | T020 |
| CA6 | T021 | T002, T020 |
| CA7 | T040 | T091 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T002, T020 | T021 |
| TM2 | T020 | T021 |
| TM3 | T020 | T022, T023 |
| TM4 | T020 | T010, T040 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| composer.lock | T002, T020 |
| tests/Feature/DependenciasSegurasTest.php | T010, T024 |
| docs/security.md | T091 |
| docs/roadmap.md | T098 (`/release`) |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Sin cambios visibles para ningún rol | T022 (suite completa) |
| Liberar antes del 2026-10-31 | T098 (fecha del tag, en `/release`) |
