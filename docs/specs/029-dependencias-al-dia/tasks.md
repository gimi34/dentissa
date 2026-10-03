---
spec: 029-dependencias-al-dia
plan: plan.md
status: approved
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
- [ ] T001 Registrar la línea base en `feature/029-dependencias-al-dia`: commit de partida (para comparar `composer.lock` en T021), recuento de `php artisan test`, salida de `composer audit --locked` sin filtro (2 bajas en `laravel/framework` y `league/flysystem`, 1 media en `phpseclib/phpseclib`), `npm audit` y las versiones de las tres bibliotecas en `composer.lock` — docs/specs/029-dependencias-al-dia/tasks.md — hecho cuando: la nota bajo esta tarea recoge los cinco datos
- [ ] T002 Repetir la simulación el día de implementar (D1, D2): `docker compose exec app composer update laravel/framework league/flysystem phpseclib/phpseclib --with-dependencies --dry-run`, consultar en Packagist (`https://repo.packagist.org/p2/<paquete>.json`, campos `time` y `source`) cada versión que cambia, y fijar con `paquete:versión` cada una que tenga menos de 7 días a la más nueva que los cumpla; volver a simular con esas fijaciones — docs/specs/029-dependencias-al-dia/tasks.md — hecho cuando: la nota recoge el comando final y la lista de versiones que cambian, todas con ≥ 7 días, de GitHub, menores o de parche, sin paquetes que aparezcan o desaparezcan y con las tres bibliotecas en versión corregida (si algo de eso no se cumple, la tarea queda con `bloqueo:` → `/plan 029 --fix`) — depende: T001

## Tests (antes de implementar)
- [ ] T010 Ampliar `DependenciasSegurasTest`: `MINIMAS_SERVIDOR` gana `laravel/framework` [12 => 12.69.0], `league/flysystem` [3 => 3.35.3] y `phpseclib/phpseclib` [3 => 3.0.57], y el docblock cita la spec 029 y los avisos del 2026-10-03 — tests/Feature/DependenciasSegurasTest.php — hecho cuando: `test_las_dependencias_del_servidor_estan_en_versiones_corregidas` falla por la razón esperada (los tres paquetes por debajo de su mínima) y el del frontend sigue en verde — cubre: CA1 — depende: T001

## Implementación
- [ ] T020 Actualizar las dependencias del servidor con el comando final de T002, sin tocar `composer.json` (controles de TM1, TM3 y TM4) — composer.lock — hecho cuando: T010 pasa, `composer audit --locked` sin filtro no reporta ningún aviso y `npm audit` reporta 0 — cubre: CA1, CA2 — depende: T002, T010
- [ ] T021 Comprobar el diff de la actualización frente al commit de T001 (tests de TM1 y TM2): los paquetes que cambian en `composer.lock` son exactamente los de T002, ninguno aparece ni desaparece y ninguno cambia de versión mayor; `require` y `require-dev` de `composer.json` siguen igual; ningún paquete nuevo es `composer-plugin`; cada versión cambiada tiene en Packagist `time` con al menos 7 días el día de T020 y `source` de GitHub — (sin archivos; nota en tasks.md) — hecho cuando: la nota recoge el resultado de cada comprobación, todas en verde — cubre: CA4, CA6 — depende: T020
- [ ] T022 Ejecutar la suite completa tras la actualización (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: `php artisan test` da el recuento de T001 en verde, sin fallos nuevos (los de `DependenciasSegurasTest` incluidos) — cubre: CA3 — depende: T020
- [ ] T023 Verificación manual del login con Google en local, hecha por el usuario tras T020 (test de TM3) — (sin archivos; nota en tasks.md) — hecho cuando: el usuario confirma que entra con Google en `http://localhost:8000` igual que antes, y la nota recoge la fecha · verificación manual: login con Google en local tras la actualización — cubre: CA5 — depende: T020

## Integración y documentación
- [ ] T040 Verificación final: `php artisan test`, `vendor/bin/pint --test tests/Feature/DependenciasSegurasTest.php`, `composer audit --locked` sin filtro con 0 avisos, `npm audit` con 0, gitleaks sobre el rango de la spec sin hallazgos y `python .ai/bin/aidd.py validate docs/specs/029-dependencias-al-dia` sin errores — (sin archivos; nota en tasks.md) — hecho cuando: todo en verde y anotado — cubre: CA7 — depende: T021, T022, T023, T091
- [ ] T090 Comprobar que `docs/architecture.md` no necesita cambios (no cita versiones de dependencias) y anotarlo
- [ ] T091 Actualizar `docs/security.md`, por corrección: RS17.a → "en curso (spec 029)", y en la fila de EX1 una nota de que sus avisos quedan corregidos en la spec 029 y que la excepción se retira al liberarla. `docs/deployment.md` y `docs/observability.md` no cambian — docs/security.md — hecho cuando: los dos cambios están y ningún riesgo figura como mitigado antes de la release — cubre: CA7 — depende: T020
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
- [ ] T098 Marcar spec como `released`: además, por CA7, EX1 sale de "Excepciones aceptadas", RS17.a → mitigada (vX.Y.Z), el texto del riesgo de dependencias de la 024 deja de citar EX1 como vigente, `composer audit --locked` sin filtro sigue sin avisos, y el tag es anterior al 2026-10-31

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T040 | T020 |
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
| tests/Feature/DependenciasSegurasTest.php | T010 |
| docs/security.md | T091 |
| docs/roadmap.md | T098 (`/release`) |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Sin cambios visibles para ningún rol | T022 (suite completa) |
| Liberar antes del 2026-10-31 | T098 (fecha del tag, en `/release`) |
