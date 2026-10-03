---
spec: 024-dependencias-seguras
status: approved   # draft | approved | blocked
created: 2026-09-29
---

# Plan · 024 Dependencias sin vulnerabilidades críticas ni altas

## Enfoque técnico
Actualización acotada de las dependencias vulnerables, sin tocar código de la aplicación. En el
servidor, `composer update` solo de `league/commonmark` y `guzzlehttp/guzzle` con sus
dependencias, dentro de las restricciones de `composer.json`: todas las subidas son menores o de
parche. En el frontend, `npm audit fix`, fijar `vite` en 8.3.0 y `rolldown` en 1.2.9 (con
`overrides`), para que toda versión que cambie, directa o transitiva, tenga al menos 7 días. Dos
tests PHPUnit nuevos comprueban que los paquetes vulnerables están en versiones corregidas: fallan
hoy y pasan después (P2 para CA1 y CA2). Las auditorías, la suite completa, la construcción de
assets y un login con Google comprueban el resto. Dos avisos de severidad baja publicados después
del plan (`laravel/framework` y `league/flysystem`) quedan fuera: se registran como excepción EX1
con vencimiento, y la auditoría de Composer ignora las severidades baja y media, como ya hace la de
npm (D10).

## Constitution Check
| Principio | Resultado | Justificación / ajuste |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 024 `approved` el 2026-09-29. |
| P2 Test que falla antes y pasa después | ❌ aceptado: CA3–CA7 sin test PHPUnit que falle antes, porque no hay comportamiento nuevo de la aplicación que probar — aprobado por el usuario el 2026-09-29 | CA1 y CA2 sí lo tienen: `DependenciasSegurasTest` falla hoy y pasa tras actualizar. CA3 (sin regresiones) es un criterio de no regresión: la suite existente pasa antes y después, así que ningún test puede fallar antes. CA4–CA7 son la construcción de assets, un login manual con un proveedor externo y restricciones sobre el propio diff. Se verifican con la suite completa, `npm run build`, la confirmación del usuario y las comprobaciones de T022. |
| P3 Autorización en servidor, por rol y recurso | ➖ | No cambia ninguna ruta ni permiso. |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. |
| P5 Nada sensible en el repo | ✅ | Solo cambian los lockfiles, `package.json` (rango de `vite` y `overrides`), un test nuevo, `docs/security.md`, `docs/roadmap.md` y el comando de auditoría en `.github/workflows/ai-dd.yml` y `.ai/project.yaml`; sin credenciales. |
| P6 Sin vulnerabilidades críticas/altas en producción | ✅ | Es el objeto de la spec: CA1 y CA2. La auditoría de Composer pasa a ignorar las severidades baja y media, como ya hace `npm audit --audit-level=high`. P6 solo exige críticas y altas, así que el umbral no lo debilita (D10). |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Principio añadido en la constitución 1.1.0 (2026-10-02), después de liberar esta spec en v0.1.0; además la spec no toca la interfaz. |

## Cambios por módulo
Las versiones de esta tabla salen de la simulación del 2026-09-29. Las definitivas son las que
resuelva la actualización al ejecutarse: se aceptan si son menores o de parche y cumplen la
antigüedad de 7 días; si no, se reabre el plan (D8).

| Módulo | Cambio | Riesgo |
|---|---|---|
| `composer.lock` | `guzzlehttp/guzzle` 7.13.2 → 7.15.5, `league/commonmark` 2.8.2 → 2.10.3, y con ellas `guzzlehttp/promises` 2.5.0 → 2.5.3, `guzzlehttp/psr7` 2.12.3 → 2.13.1, `nette/schema` v1.3.5 → v1.3.6, `nette/utils` v4.1.4 → v4.1.5, `symfony/polyfill-intl-idn` v1.38.1 → v1.42.0, `symfony/polyfill-intl-normalizer` v1.38.0 → v1.42.0 (simulación `--dry-run` del 2026-09-29; fechas en Packagist entre el 2026-07-17 y el 2026-09-21, todas de GitHub) | Medio: Guzzle es el cliente HTTP de Socialite (login con Google) |
| `package-lock.json` | `npm audit fix`: `axios` 1.13.6 → 1.20.0, `concurrently` 9.2.1 → 9.2.4, `form-data` 4.0.5 → 4.0.6, `follow-redirects` 1.15.11 → 1.16.0, `nanoid` 3.3.11 → 3.3.19, `picomatch` 4.0.3 → 4.0.7 y 2.3.1 → 2.3.2, `postcss` 8.5.8 → 8.5.28, `shell-quote` 1.8.3 → 1.9.0, `lightningcss` 1.32.0 → 1.33.0, `rolldown` 1.0.0-rc.10 → **1.2.9** (fijada), `vite` 8.0.1 → **8.3.0**. Paquetes nuevos, arrastrados por dependencias existentes: `agent-base`, `https-proxy-agent`, `debug`, `ms` y `@rolldown/binding-android-arm-eabi`; se retira `@rolldown/binding-wasm32-wasi`. Simulación con `rolldown` fijado: 48 versiones cambian, todas con ≥ 7 días, del registro oficial y con `integrity`, sin scripts de instalación | Bajo: solo `welcome.blade.php` usa Vite |
| `package.json` | Rango de `vite` `^8.0.0` → `^8.3.0` (al fijar 8.3.0) y `"overrides": { "rolldown": "1.2.9" }`; el resto de rangos no cambia y no hay dependencias directas nuevas | Bajo |
| `tests/Feature/DependenciasSegurasTest.php` (nuevo) | Dos tests: las dependencias del servidor instaladas (`Composer\InstalledVersions`) y las del frontend en `package-lock.json` están en versiones iguales o superiores a las corregidas de los avisos (tabla de Contratos y datos) | Bajo |
| `docs/security.md` | RS16.a, RS16.b y RS16.c → "en curso (spec 024)" (`/release` las pasa a mitigadas por corrección); excepción EX1 con los dos avisos bajos; comando SCA de la tabla Herramientas → `composer audit --ignore-severity=low --ignore-severity=medium` `&& npm audit --audit-level=high` (D10) | Bajo |
| `.github/workflows/ai-dd.yml` | Los dos pasos de dependencias (el job del PR y el informativo del repo completo) ejecutan `composer audit --ignore-severity=low --ignore-severity=medium` `&& npm audit --audit-level=high` (D10) | Bajo: ruta de CI; solo cambia el umbral de Composer |
| `.ai/project.yaml` | `security.tools.sca` con el mismo comando, para que `/review` y `/release` usen el mismo umbral que el CI (D10) | Bajo |
| `docs/roadmap.md` | En Pendientes: actualizar `laravel/framework` a ≥ 12.69.0 y `league/flysystem` a ≥ 3.35.3 antes del vencimiento de EX1 (2026-10-31) | Bajo |

## Contratos y datos
Sin cambios de rutas, respuestas, modelo de datos ni migraciones. Sin cambios de pantallas: los
assets construidos por Vite solo los usa `welcome.blade.php`, que ninguna ruta sirve.

**Versiones mínimas corregidas** (según los avisos de `composer audit` y `npm audit` del
2026-09-29; las comprueba `DependenciasSegurasTest`):

| Paquete | Mínima corregida | Paquete | Mínima corregida |
|---|---|---|---|
| `guzzlehttp/guzzle` | 7.15.2 | `form-data` | 4.0.6 |
| `league/commonmark` | 2.10.0 | `nanoid` | 3.3.18 |
| `axios` | 1.17.1 | `picomatch` | 2.3.2 (rama 2) y 4.0.4 (rama 4) |
| `concurrently` | 9.2.2 | `postcss` | 8.5.23 |
| `vite` | 8.0.16 | `shell-quote` | 1.8.5 |
| `follow-redirects` | 1.15.12 | | |

## Estrategia de pruebas
- **Tests de versión (CA1, CA2):** `tests/Feature/DependenciasSegurasTest.php`
  (`test_las_dependencias_del_servidor_estan_en_versiones_corregidas`,
  `test_las_dependencias_del_frontend_estan_en_versiones_corregidas`) contra la tabla de versiones
  mínimas. Fallan hoy y pasan tras T020/T021; después impiden que vuelva un lockfile vulnerable.
- **Verificación de seguridad (CA1, CA2):** `docker compose exec app composer audit
  --ignore-severity=low --ignore-severity=medium` y `npm audit --audit-level=high` terminan con
  código 0: el job de dependencias del PR queda en verde. Las versiones nuevas corrigen las 9
  altas y los 7 medios del servidor; `composer audit` sin filtro solo muestra los 2 avisos bajos
  de EX1 (D10).
- **Regresión (CA3):** `php artisan test` completo contra `dentissa_test`, con la misma línea
  base que la rama de partida (`chore/ai-dd-init`: 22 tests en verde).
- **Construcción (CA4):** `npm run build` sin errores (línea base del 2026-09-29: construye en ~2 s).
- **Login con Google (CA5):** verificación manual del usuario en local, anotada con fecha
  (decisión del usuario, 2026-09-29).
- **Versiones mayores (CA6):** sobre el diff completo de ambos lockfiles. "Dependencia que se
  ejecuta en producción" es todo paquete de `packages` (no `packages-dev`) de `composer.lock`,
  directo o transitivo, y todo paquete npm que acaba en los assets construidos (`public/build`).
  Ninguno cambia de versión mayor. La lista de paquetes npm de los assets sale del grafo real de
  la construcción, igual antes (T002) y después (T022): se construye con sourcemaps en un
  directorio temporal y se listan los `node_modules/<paquete>` distintos de `sources` (buscar
  nombres en el bundle minificado no sirve: no los conserva). El único salto mayor de la
  simulación, `proxy-from-env` 1 → 2, solo lo usa `axios` en Node (su campo `browser` excluye el
  adaptador HTTP); la comprobación es que no aparece en esa lista.
- **Cadena de suministro (CA7):** `package.json` y `composer.json` no ganan dependencias
  directas. **Cada versión que cambia en cualquiera de los dos lockfiles**, directa o transitiva,
  lleva al menos 7 días publicada (`npm view <paquete> time`, campo `time` de Packagist). En npm,
  cada entrada nueva o cambiada tiene `resolved` bajo `https://registry.npmjs.org/` e
  `integrity`; en Composer, cada paquete cambiado viene de Packagist o GitHub. El diff no añade
  paquetes con `hasInstallScript`. El lockfile conserva las entradas linux-x64 (gnu/musl) de
  `@rolldown/binding-*` y `lightningcss-*`, necesarias para construir en Linux. Todo se comprueba
  sobre las versiones realmente resueltas (D8).

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: una versión maliciosa o un paquete de nombre parecido entra como dependencia, directa o transitiva | A03:2025 | `package.json`, `composer.json`, ambos lockfiles | Ninguna dependencia directa nueva; toda versión que cambia con ≥ 7 días (`rolldown` fijada en 1.2.9 por esa regla); solo el registro oficial, con `integrity` | Comprobación CA7 (T022): diff de `require`/`devDependencies` sin altas, fechas de publicación ≥ 7 días de cada versión cambiada, `resolved` e `integrity` de cada entrada npm y origen de cada paquete de Composer |
| TM2 | Elevation of privilege: una transitiva nueva ejecuta código al instalarse | A03:2025 | `package-lock.json` | Actualizar con `--ignore-scripts` y revisar que el lockfile no añade paquetes con `hasInstallScript` | Comprobación sobre el diff del lockfile: 0 entradas nuevas con `hasInstallScript` |
| TM3 | Denial of service / tampering por un cambio de comportamiento en un componente actualizado (cliente HTTP del OAuth, parser Markdown) | A08:2025 | `guzzlehttp/guzzle`, `league/commonmark` | Solo subidas menores o de parche en el servidor; suite completa; login con Google | CA3 (`php artisan test`), CA5 (login manual) |
| TM4 | Vulnerabilidades conocidas en componentes (residuales) | A03:2025 | dependencias de servidor y frontend | Actualización a versiones corregidas | `DependenciasSegurasTest`; CA1 (`composer audit --ignore-severity=low --ignore-severity=medium`), CA2 (`npm audit --audit-level=high`) |

Casos de abuso de la spec → amenazas: CA7 → TM1, TM2.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: dependencias del servidor sin críticas ni altas | `composer.lock` | Feature `DependenciasSegurasTest::test_las_dependencias_del_servidor_estan_en_versiones_corregidas`; verificación `composer audit --ignore-severity=low --ignore-severity=medium` con código 0 (hoy: 9 altas, 7 medias y 2 bajas; tras actualizar solo quedan las 2 bajas de EX1) |
| CA2: dependencias del frontend sin críticas ni altas, incluidas las de desarrollo | `package-lock.json`, `package.json` | Feature `DependenciasSegurasTest::test_las_dependencias_del_frontend_estan_en_versiones_corregidas`; verificación `npm audit --audit-level=high` con código 0 (hoy: 2 críticas y 6 altas) |
| CA3: suite completa sin fallos nuevos | `composer.lock` | `php artisan test` frente a la línea base de la rama |
| CA4: construcción del frontend sin errores | `package-lock.json`, `package.json` | `npm run build` |
| CA5: login con Google igual que antes | `composer.lock` (Guzzle) | Verificación manual del usuario en local |
| CA6: sin saltos mayores del framework ni de dependencias de producción | `composer.lock`, `package-lock.json` | Comparación de versiones mayores sobre el diff completo de ambos lockfiles; lista de paquetes de los assets obtenida de los sourcemaps de la construcción, sin `proxy-from-env` y sin saltos mayores |
| CA7 (abuso): sin directas nuevas y toda versión que cambia con ≥ 7 días | `package.json`, `composer.json`, ambos lockfiles | Comprobación de directas, fechas de publicación, origen e integridad de cada versión cambiada (TM1) |
| TM1 | directas sin altas; toda versión cambiada con ≥ 7 días; registro oficial e `integrity` | Comprobación CA7 |
| TM2 | `--ignore-scripts`; sin `hasInstallScript` nuevos | Comprobación sobre el diff del lockfile |
| TM3 | subidas menores; suite; login | `php artisan test`; login manual |
| TM4 | versiones corregidas | `DependenciasSegurasTest`; `composer audit --ignore-severity=low --ignore-severity=medium`; `npm audit --audit-level=high` |

## Observabilidad
No aplica: la spec no cambia datos sensibles, autenticación ni permisos, y no añade logs, eventos
de auditoría ni métricas.

## Rollout
- Feature flag: no aplica. No hay comportamiento nuevo que encender o apagar; una dependencia no
  se puede activar a medias.
- Orden de despliegue: un único paso. En el servidor, `composer install --no-dev` con el lock
  nuevo; en assets, `npm ci && npm run build`. Sin migraciones.
- Compatibilidad: sin cambios de contrato; la versión anterior y la nueva sirven las mismas
  respuestas.
- Rollback: redeploy del tag anterior, que restaura los lockfiles anteriores. Vuelve a dejar las
  vulnerabilidades, así que solo se usa ante una regresión, y hay que reabrir RS16.
- Métricas a vigilar tras el deploy: errores 500 y fallos del login con Google en el log.
- Integración y releases (decisión del usuario, 2026-09-29):
  1. Se implementa en `feature/024-dependencias-seguras`, creada desde `chore/ai-dd-init` con los
     commits de la spec, el plan, las tareas y el análisis de la 024 traídos con `cherry-pick`.
  2. `chore/ai-dd-init` se integra en `main` antes que la 024, con merge commit y sin squash ni
     rebase, para que `main` tenga el flujo AI-DD y el CI sin reescribir sus commits.
  3. El PR de la 024 va contra `main`, con el job de dependencias en verde, y la 024 se libera
     sola. Antes de abrirlo, `git log --oneline main..feature/024-dependencias-seguras` solo
     muestra commits de la 024.
  4. `feature/020-auditoria-base` se reconstruye sobre `main` sin sus copias de los commits de la
     024. Comprobación: tras integrar la 020, entre el `main` anterior y el nuevo no cambian ni
     `docs/specs/024-dependencias-seguras` ni la fila 024 de `docs/specs/README.md` ni su enlace
     en `docs/roadmap.md`.
  5. Antes de `/release 020`, el merge de la 024 es ancestro del commit que se libera
     (`git merge-base --is-ancestor`), y `composer audit` y `npm audit --audit-level=high` dan
     0 críticas y 0 altas sobre ese árbol.
  Los pasos 4 y 5 se ejecutan en el ciclo de la 020, así que quedan anotados en "Pendientes y
  deuda" del roadmap (decisión del usuario, 2026-09-29).

## Decisiones (→ ADR si son arquitectónicas)
- D1 · Servidor: `composer update league/commonmark guzzlehttp/guzzle --with-dependencies`
  dentro del contenedor `app` (el lock exige PHP ≥ 8.4.1, RD7), sin tocar `composer.json`. Se
  descarta un `composer update` completo, que arrastraría Laravel y el resto sin necesidad. Cubre
  RS16.a.
- D2 · Frontend: `npm audit fix --ignore-scripts`, después `npm install --save-dev vite@8.3.0
  --ignore-scripts`, y `"overrides": { "rolldown": "1.2.9" }` en `package.json` con un
  `npm install --ignore-scripts`.
  - `vite`: la versión corregida mínima es 8.0.16 (2026-06-01, la última de la rama 8.0). Se
    descarta porque se queda atrás en correcciones posteriores. La 8.3.1, que elegiría la
    corrección automática, se publicó el 2026-09-24 y no cumple los 7 días; la 8.3.0 (2026-09-10)
    sí, y corrige el aviso (rango afectado 8.0.0–8.0.15) (decisión del usuario, 2026-09-29).
  - `rolldown`: `vite@8.3.0` pide `~1.2.6`, y la resolución automática elige 1.2.11
    (2026-09-24), que no cumple los 7 días. Se fija la 1.2.9 (2026-09-16), la más reciente que los
    cumple, con `overrides` (no es una dependencia directa nueva).
  - Simulación sobre una copia del lockfile: 0 vulnerabilidades, 48 versiones cambiadas con
    ≥ 7 días, todas del registro oficial con `integrity`, sin scripts de instalación, con los
    binarios linux-x64 de `rolldown` y `lightningcss`, y un único salto mayor transitivo
    (`proxy-from-env`, solo en Node).
  - Cubre RS16.b.
- D3 · Paquetes nuevos (`agent-base`, `https-proxy-agent`, `debug`, `ms` y
  `@rolldown/binding-android-arm-eabi`): llegan por dependencias directas existentes al
  actualizarse, no se añaden a mano. Pasan la regla de antigüedad y de origen de CA7 y TM2 (sin
  scripts de instalación). `shared/agent-security.md` §2 aplica a dependencias directas nuevas, y
  no hay ninguna. `lightningcss` ya estaba en el lockfile (1.32.0 → 1.33.0).
- D4 · Verificación del login con Google (CA5): manual, por el usuario en local (decisión del
  usuario, 2026-09-29).
- D5 · Tests PHPUnit de versión para CA1 y CA2 (`DependenciasSegurasTest`), y P2 aceptado como
  desviación para CA3–CA7 (decisión del usuario, 2026-09-29; CA3 añadido tras `/analyze` ronda 2). Si esta desviación se repite en
  otras specs sin cambio de comportamiento, conviene enmendar P2 para distinguir los cambios de
  comportamiento del resto.
- D6 · Cobertura de riesgos: RS16.a, RS16.b y RS16.c dentro (spec 024 completa); pasan a "en
  curso (spec 024)" en la implementación y a mitigadas en `/release`. RD7.a sigue pendiente
  (objetivo 5).
- D7 · Rama, orden de integración y releases: ver Rollout (decisión del usuario, 2026-09-29).
- D8 · Versiones resueltas al implementar: si difieren de la simulación, se aceptan si son menores
  o de parche y cumplen la antigüedad y el origen de CA7; si no, se detiene la implementación y se
  reabre el plan (decisión del usuario, 2026-09-29).
- D9 · Antigüedad mínima de 7 días para toda versión que cambie, directa o transitiva, en ambos
  lockfiles (spec CA7; decisión del usuario, 2026-09-29).
- D10 · Avisos bajos publicados después del plan y detectados en T010 (2026-09-29):
  - `laravel/framework` 12.62.0 (CVE-2026-102279, corregido en 12.69.0) y `league/flysystem`
    3.35.1 (CVE-2026-102601, corregido en 3.35.3). No se corrigen en la 024: actualizar Laravel
    arrastra 42 paquetes, y la 12.69.3 no cumple los 7 días.
  - Se registran como excepción EX1 en `docs/security.md` (aprobada por el usuario el
    2026-09-29, vence el 2026-10-31) y quedan en Pendientes del roadmap.
  - La auditoría de Composer ignora las severidades baja y media en el CI, `/review` y
    `/release` (`composer audit --ignore-severity=low --ignore-severity=medium`), igual que
    `npm audit --audit-level=high`. P6 solo exige críticas y altas.
  - Decisión del usuario, 2026-09-29.

## Impacto en arquitectura
- `docs/architecture.md`: sin cambios (no cita versiones de dependencias).
- `docs/security.md → Riesgos conocidos → RS16`: estado por corrección (T091) y actualizar el
  recuento de la línea base. `Excepciones aceptadas` → EX1, y `Herramientas` → comando SCA (D10).

## Riesgos y mitigaciones
- Una subida menor de Guzzle cambia algún detalle del OAuth con Google → login manual (CA5) antes
  de dar la tarea por hecha; si falla, se revierte solo `composer.lock` y se reabre el plan.
- `vite` 8.0 → 8.3 rompe la construcción → `npm run build` (CA4). Solo afecta a
  `welcome.blade.php`, sin uso.
- Aparece una vulnerabilidad nueva antes de liberar (p. ej. en `vite@8.3.0`) → `/release` vuelve
  a ejecutar `composer audit` y `npm audit`; si aparece, se reevalúa con 8.3.1, que ya tendrá más
  de 7 días.
- `npm ls` marca como `extraneous` en `node_modules` algunos paquetes opcionales de desarrollo
  que sí están en el lockfile (`@emnapi/*`, `@napi-rs/wasm-runtime`, `@tybys/wasm-util`) → no
  afectan a `npm audit` y `npm ci` deja `node_modules` alineado con el lockfile.
- `overrides` fija `rolldown` en 1.2.9; cuando la 1.2.11 o posterior cumpla los 7 días, una
  actualización futura puede retirar la fijación (anotado en `docs/security.md` con RS16).
- Rollback reintroduce las vulnerabilidades → documentado en Rollout; RS16 se reabre si se usa.
- Los avisos bajos de EX1 siguen hasta actualizar Laravel y Flysystem → vencimiento el 2026-10-31
  y anotación en Pendientes del roadmap.
- Un aviso medio o bajo nuevo de Composer ya no pone el job en rojo → P6 no lo exige; se ve con
  `composer audit` sin filtro.
