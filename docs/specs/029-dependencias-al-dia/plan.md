---
spec: 029-dependencias-al-dia
status: approved   # aprobado por el usuario el 2026-10-03
created: 2026-10-03
---

# Plan · 029 Dependencias sin avisos de seguridad conocidos

## Enfoque técnico
La actualización se limita a las tres bibliotecas con aviso y no toca código de la aplicación.
Dentro del contenedor `app` se ejecuta `composer update` de `laravel/framework`, `league/flysystem`
y `phpseclib/phpseclib` con `--with-dependencies` y dentro de las restricciones de `composer.json`.
Todas las subidas son menores o de parche.

Para cumplir los 7 días de antigüedad, cada paquete cuya última versión resuelta sea más reciente
se fija en la línea de comandos a la versión más nueva que sí los cumpla el día de implementar. En
la simulación de hoy son seis, entre ellas Laravel 12.69.2, que ya corrige su aviso. Las
restricciones de la línea de comandos son temporales: `composer.json` no cambia.

`DependenciasSegurasTest` (spec 024) gana las tres versiones mínimas corregidas: falla hoy y pasa
después. `composer audit` sin filtro de severidad comprueba que no queda ningún aviso. La suite,
el diff del lockfile, las fechas de Packagist y un login con Google comprueban el resto.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 029 `approved` el 2026-10-03. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Constitución 1.2.1 (decisión del usuario, 2026-10-03). CA1 cambia el estado de las dependencias: `DependenciasSegurasTest` con las tres mínimas nuevas falla hoy y pasa después. CA2 lo cubre el test del frontend de la 024 (caracterización, comprobado en la 024; `package-lock.json` no cambia). CA3–CA7 no describen comportamiento de la aplicación (no regresión, contenido del diff, datos de Packagist, login manual, estado de `security.md`): se verifican con las comprobaciones de la Trazabilidad, anotadas en su tarea. | test: `DependenciasSegurasTest::test_las_dependencias_del_servidor_estan_en_versiones_corregidas` (fallo previo anotado) · manual: comprobaciones de CA3–CA7 anotadas en sus tareas |
| P3 Autorización en servidor | ➖ | No cambia ninguna ruta ni permiso. | — |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas ni SQL. | — |
| P5 Nada sensible en el repo | ✅ | Solo cambian `composer.lock`, un test y la documentación. | lint: gitleaks sobre el rango de la spec |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Objeto de la spec, con un umbral más estricto: ningún aviso de ninguna severidad (spec, decisión del usuario). | lint: `composer audit --locked` sin `--ignore-severity` y `npm audit`, ambos con 0 avisos |
| P7 Auditoría de datos sensibles | ➖ | Sin accesos nuevos a datos sensibles. | — |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos. Monolog sube de versión menor sin cambios de configuración. | — |
| P9 La interfaz usa solo el sistema de diseño | ➖ | La spec no toca la interfaz. | — |

## Cambios por módulo
Las versiones salen de la simulación `--dry-run` del 2026-10-03, con las seis fijaciones de D1. Las
definitivas son las que resuelva la actualización el día de implementar, con la misma regla (D2).

| Módulo | Cambio | Riesgo |
|---|---|---|
| `composer.lock` | 41 paquetes, todos de GitHub vía Packagist y con al menos 7 días. Las tres con aviso: `laravel/framework` v12.62.0 → v12.69.2, `league/flysystem` 3.35.1 → 3.36.0 (y `league/flysystem-local` 3.31.0 → 3.35.3) y `phpseclib/phpseclib` 3.0.55 → 3.0.57. Las arrastradas: `carbonphp/carbon-doctrine-types` 3.2.0 → 3.2.1, `doctrine/lexer` 3.0.1 → 3.0.2, `graham-campbell/result-type` v1.1.4 → v1.2.0, `guzzlehttp/uri-template` v1.0.8 → v1.0.11, `laravel/prompts` v0.3.21 → v0.3.24, `laravel/serializable-closure` v2.0.13 → v2.1.0, `league/mime-type-detection` 1.16.0 → 1.17.0, `monolog/monolog` 3.10.0 → 3.12.0, `nesbot/carbon` 3.13.0 → 3.14.0, `phpoption/phpoption` 1.9.5 → 1.10.0, `ramsey/uuid` 4.9.3 → 4.9.4, `vlucas/phpdotenv` v5.6.3 → v5.7.0 y 25 de Symfony en sus ramas 7.4, 8.1, 3.7 y 1.x (`console`, `error-handler`, `finder`, `http-foundation`, `uid` → v7.4.20; `http-kernel`, `mailer`, `mime`, `process` → v7.4.19; `routing` → v7.4.18; `var-dumper` → v7.4.18; `css-selector` → v8.1.6; `event-dispatcher` → v8.1.5; `string` → v8.1.7; `translation` → v8.1.5; `service-contracts` → v3.7.3; y los 9 `polyfill-*` → v1.43.0) | Medio: el framework y Symfony sostienen toda la app; Monolog y Carbon tocan logs y fechas |
| `tests/Feature/DependenciasSegurasTest.php` | `MINIMAS_SERVIDOR` gana `laravel/framework` 12 → 12.69.0, `league/flysystem` 3 → 3.35.3 y `phpseclib/phpseclib` 3 → 3.0.57; el docblock cita la spec 029 | Bajo |
| `docs/security.md` | RS17.a → "en curso (spec 029)" (T091). En `/release`: RS17.a → mitigada, EX1 sale de "Excepciones aceptadas" y el texto del riesgo de dependencias de la 024 deja de citar EX1 como vigente | Bajo |
| `docs/roadmap.md` | En `/release`: la entrada de dependencias dice que la 029 atendió EX1 y RS17 | Bajo |

`composer.json`, `package.json`, `package-lock.json`, el CI y `.ai/project.yaml` no cambian.

## Contratos y datos
Sin cambios de rutas, respuestas, modelo de datos, migraciones ni pantallas.

**Versiones mínimas corregidas** (avisos de `composer audit` del 2026-10-03; las comprueba
`DependenciasSegurasTest`):

| Paquete | Aviso | Mínima corregida |
|---|---|---|
| `laravel/framework` | CVE-2026-102279 (baja) | 12.69.0 |
| `league/flysystem` | CVE-2026-102601 (baja) | 3.35.3 |
| `phpseclib/phpseclib` | CVE-2026-84308 (media) | 3.0.57 |

## Estrategia de pruebas
- **CA1 (cambia el estado de las dependencias):**
  - `DependenciasSegurasTest::test_las_dependencias_del_servidor_estan_en_versiones_corregidas` con las tres mínimas nuevas: falla hoy (tres paquetes por debajo) y pasa tras actualizar;
  - `composer audit --locked`, sin filtro de severidad, sin ningún aviso.
- **CA2 (ya se cumple):** el test del frontend de la 024 y `npm audit` con 0. `package-lock.json` no cambia.
- **CA3 (no regresión):** `php artisan test` completo frente a la línea base de la rama (152 en verde el 2026-10-03).
- **CA4 (contenido del diff):** comparación de `composer.lock` antes y después:
  - solo cambian paquetes de la lista de la simulación (o de la resolución del día, D2);
  - ninguno cambia de versión mayor;
  - no aparece ni desaparece ningún paquete;
  - `require` y `require-dev` de `composer.json` siguen igual;
  - ningún paquete nuevo es de tipo `composer-plugin`.
- **CA5 (comprobación manual):** login con Google en local, anotado con fecha (Socialite usa Guzzle, que no cambia, y el framework, que sí).
- **CA6 (datos de un servicio externo):** para cada versión que cambia, el campo `time` de Packagist (`https://repo.packagist.org/p2/<paquete>.json`) tiene al menos 7 días el día de implementar, y su `source` es de GitHub.
- **CA7 (estado de la documentación, en `/release`):** en `docs/security.md`, EX1 no está en "Excepciones aceptadas", RS17.a figura como mitigada con su versión, y `composer audit` sin filtro no deja ningún aviso sin registrar.
- **RNF (antes del 2026-10-31):** fecha del tag de la release.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: una versión maliciosa o un paquete de nombre parecido entra en la actualización | A03:2025 | `composer.lock`, `composer.json` | Sin dependencias directas nuevas; solo cambian los paquetes de la resolución; toda versión que cambia lleva ≥ 7 días y viene de GitHub vía Packagist | Comprobaciones de CA4 y CA6 (T022) |
| TM2 | Elevation of privilege: un paquete nuevo ejecuta código como plugin de Composer al instalarse | A03:2025 | `composer.lock` | No aparece ningún paquete; ninguno nuevo es `composer-plugin` | Comprobación de CA4 |
| TM3 | Tampering o DoS por un cambio de comportamiento del framework o de Symfony | A08:2025 | `laravel/framework`, `symfony/*`, `monolog`, `carbon` | Solo subidas menores o de parche; suite completa; login con Google | CA3 (`php artisan test`), CA5 (login manual) |
| TM4 | Vulnerabilidades conocidas que siguen tras la actualización | A03:2025 | dependencias del servidor | Versiones corregidas | `DependenciasSegurasTest`; `composer audit --locked` sin filtro |

Casos de abuso de la spec → amenazas: CA6 → TM1 y TM2.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: dependencias del servidor sin ningún aviso | `composer.lock`, `DependenciasSegurasTest` | Feature `DependenciasSegurasTest::test_las_dependencias_del_servidor_estan_en_versiones_corregidas` (falla antes); `composer audit --locked` sin filtro con 0 avisos |
| CA2: dependencias del frontend sin vulnerabilidades | ninguno | Feature `DependenciasSegurasTest::test_las_dependencias_del_frontend_estan_en_versiones_corregidas` (caracterización, 024); `npm audit` con 0 |
| CA3: suite completa sin fallos nuevos | `composer.lock` | Comprobación (P2 1.2.1): `php artisan test` frente a la línea base |
| CA4: solo cambian las tres y lo arrastrado, sin saltos mayores ni directas nuevas | `composer.lock` | Comprobación (P2 1.2.1): comparación de `composer.lock` y `composer.json` antes y después |
| CA5: login con Google igual que antes | `composer.lock` | Verificación manual del usuario en local |
| CA6 (abuso): toda versión que cambia con ≥ 7 días | `composer.lock` | Comprobación (P2 1.2.1): fechas y origen en Packagist de cada versión cambiada |
| CA7: EX1 retirada, RS17 mitigado, sin avisos sin registrar | `docs/security.md` | Comprobación en `/release` (P2 1.2.1): `docs/security.md` y `composer audit` sin filtro |
| TM1 | resolución limitada; ≥ 7 días; origen | Comprobaciones de CA4 y CA6 |
| TM2 | sin paquetes ni plugins nuevos | Comprobación de CA4 |
| TM3 | subidas menores; suite; login | `php artisan test`; login manual |
| TM4 | versiones corregidas | `DependenciasSegurasTest`; `composer audit --locked` |
| RNF: sin cambios visibles para ningún rol | `composer.lock` | `php artisan test` (CA3) |
| RNF: liberar antes del 2026-10-31 | release | Fecha del tag (comprobación en `/release`) |

## Observabilidad
No aplica: la spec no cambia datos sensibles, autenticación ni permisos, y no añade logs, eventos
de auditoría ni métricas. Monolog sube de versión menor sin cambios en `config/logging.php`.

## Rollout
- Sin despliegue configurado: no hay staging ni producción (`docs/deployment.md`, objetivo 5). Se libera como la 027: PR contra `main` con merge commit, y después tag.
- Feature flag: no aplica. Una dependencia no se puede activar a medias.
- Orden: un único paso. `composer install` con el lock nuevo; sin migraciones.
- Compatibilidad: sin cambios de contrato.
- Rollback: volver al tag anterior, que restaura `composer.lock`. Reabre RS17 y los avisos de EX1, así que solo se usa ante una regresión.
- Qué vigilar: errores 500 y fallos del login con Google.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Actualización acotada con versiones fijadas por antigüedad.**
  - Opciones: (a) actualizar solo las tres con aviso y lo que arrastran; (b) `composer update` completo; (c) esperar al 2026-10-09, cuando todas las versiones resueltas cumplen los 7 días.
  - Se elige (a), con este comando dentro del contenedor `app`:
    `composer update laravel/framework league/flysystem phpseclib/phpseclib --with-dependencies`.
  - Cada paquete cuya versión resuelta tenga menos de 7 días se fija en la línea de comandos (`paquete:versión`) a la versión más nueva que sí los cumpla (decisión del usuario, 2026-10-03).
  - En la simulación del 2026-10-03 son seis: `laravel/framework:12.69.2` (12.69.3 es del 2026-09-29), `monolog/monolog:3.12.0` (3.12.1, 2026-09-29), `symfony/http-kernel:7.4.19` (7.4.20, 2026-09-29), `symfony/routing:7.4.18` (7.4.20, 2026-09-28), `doctrine/lexer:3.0.2` (3.0.3, 2026-10-01) y `nesbot/carbon:3.14.0` (3.14.1, 2026-09-27; 3.14.2, 2026-10-02).
  - Resultado de la simulación: 41 actualizaciones, ningún paquete nuevo ni retirado, todas con ≥ 7 días y de GitHub.
  - Cubre RS17.a y los avisos de EX1.
- **D2 · Resolución del día.** El día de implementar se repite la simulación con la misma regla.
  - Si las versiones difieren (por publicaciones nuevas), se aceptan si son menores o de parche, cumplen los 7 días, vienen de GitHub y no añaden paquetes.
  - Si no, se detiene la implementación y se reabre el plan.
- **D3 · Constitución 1.2.1** (decisión del usuario, 2026-10-03): P2 permite verificar con una comprobación declarada los criterios que no describen comportamiento (CA3–CA7). Evita repetir la excepción de la 024.
- **D4 · Umbral de CA1:** `composer audit --locked` sin `--ignore-severity`. El CI, `/review` y `/release` siguen ignorando las severidades baja y media (024, D10), y cambiarlo queda fuera de alcance (spec).
- **D5 · Test:** se amplía `DependenciasSegurasTest` en lugar de crear otro, con el mismo patrón por rama mayor.
- **D6 · Riesgos:** RS17.a pasa a "en curso (spec 029)" al implementar y a mitigada en `/release`; EX1 se retira en `/release` (spec CA7).
- **D7 · Plazo de EX1:** si al implementar alguna de las tres no tiene ninguna versión corregida con ≥ 7 días, se usa la anterior corregida que sí los cumpla. Si no hay ninguna, se renueva EX1 solo para ese paquete, con fecha nueva y aprobación del usuario (spec, Decisiones). Hoy no hace falta: las tres tienen versión corregida con ≥ 7 días.

## Impacto en arquitectura
- `docs/architecture.md`: sin cambios (no cita versiones de dependencias).
- `docs/security.md`: RS17.a "en curso" (T091); en `/release`, EX1 retirada, RS17.a mitigada y el texto del riesgo de dependencias de la 024 sin EX1 vigente.

## Riesgos y mitigaciones
- **Laravel 12.62 → 12.69 trae siete versiones menores de cambios** (y Symfony 7.4.14 → 7.4.20) → la suite completa (152 tests) y el login con Google. Si algo falla, se revierte solo `composer.lock` y se reabre el plan.
- **Monolog 3.10 → 3.12 y Carbon 3.13 → 3.14** podrían cambiar el formato de logs o fechas → los tests de agenda y estados usan Carbon y la zona de la clínica (`ReglaAgendaTest`, `EstadosCita*`, `CitasFueraDelHorarioTest`); sin cambios en `config/logging.php`.
- **Aparece un aviso nuevo antes de liberar** → `/release` vuelve a ejecutar `composer audit` sin filtro; si aparece, se trata con la regla de la spec (excepción con fecha si no hay corrección con 7 días).
- **Las versiones fijadas no son las últimas** (p. ej. Laravel 12.69.2 y no 12.69.3) → la próxima actualización las alcanzará; no corrigen ningún aviso pendiente.
- **La rama de la 020** (`feature/020-auditoria-base`, aprobada y sin integrar) se basa en un `main` anterior → al reconstruirla sobre `main` (pendiente del roadmap) heredará el lock nuevo; ya estaba previsto reconstruirla.
