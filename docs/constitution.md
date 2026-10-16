---
version: 1.3.0
ratified: 2026-09-28
last_amended: 2026-10-16
status: approved   # aprobada por el usuario el 2026-09-28
---

# Constitución de Dentissa

Principios innegociables. Toda spec, plan y tarea debe cumplirlos. `/plan` y `/tasks` incluyen un
**Constitution Check** que evalúa cada principio; un incumplimiento sin justificación bloquea.

Principios elegidos por el usuario en la entrevista de `/init` (decisión del usuario, 2026-09-28).

## Código previo
El código existente anterior a esta constitución que no cumple un principio **no bloquea** por sí
mismo: queda registrado como deuda en [roadmap.md](roadmap.md) y en las "Observaciones" de cada
spec. Los principios se exigen a todo código **nuevo o modificado**.

## Principios

### P1. Spec aprobada antes de código
**Regla:** ninguna implementación de comportamiento nuevo o modificado sin una spec en
`docs/specs/NNN-<slug>/spec.md` con `status: approved` y su `tasks.md` aprobado.
**Cómo se verifica:** `python .ai/bin/aidd.py status`; el hook del plugin (`workflow.enforcement`)
pide aprobación al editar código sin spec activa; `/review` rechaza cambios sin spec.
**Por qué:** trabajo en equipo académico con varias personas tocando los mismos controladores; la
spec fija el qué y el por qué antes de discutir el cómo.

### P2. Todo cambio de comportamiento lleva test que falla antes y pasa después
**Regla:** cada criterio de aceptación de una spec tiene al menos un test PHPUnit (Feature o Unit).
- Si el criterio **cambia comportamiento**, su test falla sin el cambio y pasa con él.
- Si el criterio describe algo que **ya se cumple** (p. ej. un rechazo por rol que el middleware
  ya hace), su test es de **caracterización**. Pasa desde el principio y se comprueba rompiendo un
  momento el código que protege: debe fallar, se restaura y se anota en la tarea (`comprobado
  rompiendo <qué>`).
- Si el criterio **no describe comportamiento de la aplicación** (que no haya regresiones, el
  contenido de un diff o de un lockfile, datos de un servicio externo como un registro de
  paquetes, o una comprobación que solo puede hacer una persona), se verifica con el comando o la
  comprobación que declara la Trazabilidad del plan, y su resultado se anota en la tarea. No
  necesita test PHPUnit propio, y no puede usarse para un criterio que sí cambia comportamiento.

Los tests usan `DatabaseTransactions` contra la base de pruebas, nunca contra la base de
desarrollo.
**Cómo se verifica:** sección Trazabilidad del plan (criterio → test, marcando los de
caracterización); `/implement` registra el fallo previo o la rotura comprobada en la nota de la
tarea; `/review` comprueba que cada test existe, que `php artisan test` pasa y que cada
caracterización tiene su comprobación.
**Por qué:** hoy solo usuarios y servicios tienen tests; agenda, pacientes y expedientes no tienen
red de seguridad.

### P3. Autorización verificada en el servidor, por rol y por recurso
**Regla:** todo endpoint nuevo o modificado verifica (1) el rol con middleware y (2) el alcance
sobre el recurso concreto (dentista → sus pacientes y expedientes; asistente → los de sus
doctores asignados; cliente → solo lo suyo) mediante una Policy o Gate de Laravel. Ningún control
de acceso vive solo en la vista.
**Cómo se verifica:** test Feature por rol que cubre el caso permitido y el denegado (403) por
cada acción; `/review` con `shared/security-checklist.md`.
**Por qué:** el sistema maneja expedientes clínicos; el acceso horizontal entre personal es el
riesgo más grave detectado (docs/security.md).

### P4. Entrada validada en el servidor y SQL siempre con bindings
**Regla:** toda entrada externa se valida en el servidor (reglas de `validate` o Form Request),
incluidos formatos de fecha y hora, arrays y claves foráneas con su tipo; toda consulta usa
Query Builder/Eloquent con bindings; prohibido concatenar entrada del usuario en SQL, `DB::raw`,
`whereRaw` u `orderByRaw`. En Blade solo `{{ }}` o `@js()`; `{!! !!}` requiere justificación en el plan.
**Cómo se verifica:** SAST (`semgrep`) sobre los archivos tocados; tests de validación por campo;
revisión.
**Por qué:** evita inyección y errores 500 por entradas mal formadas.

### P5. Nada sensible en el repositorio
**Regla:** no se versionan secretos, contraseñas (tampoco de cuentas de prueba), `.env`, volcados
con datos personales o de salud (aunque sean ficticios con apariencia real) ni credenciales de
base de datos distintas de las de un entorno local desechable documentado. Los datos de ejemplo
salen de seeders con datos sintéticos (Faker).
**Cómo se verifica:** `gitleaks detect --no-banner` en `/implement` y en CI; revisión de diffs de
`database/`, `README.md` y archivos de configuración.
**Por qué:** marco LFPDPPP / NOM-024 y buenas prácticas aunque los datos actuales sean ficticios.

### P6. Ninguna vulnerabilidad crítica o alta llega a producción sin excepción aprobada
**Regla:** `/release` bloquea si `composer audit`, `npm audit --audit-level=high`, `semgrep` o
`trivy` reportan hallazgos críticos o altos sin una excepción vigente (con motivo, aprobador y
fecha de vencimiento) en `docs/security.md → Excepciones aceptadas`.
**Cómo se verifica:** salida de las herramientas en `/review` y `/release`; job de CI.
**Por qué:** ASVS nivel 2 y datos de salud.

### P7. Auditoría de accesos y cambios a datos sensibles
**Regla:** los logins (exitosos y fallidos, también con Google), logouts, accesos denegados
(403), lecturas de ficha de paciente y expedientes, cambios de pacientes, expedientes y notas de
citas, y altas, cambios de rol, activación/desactivación y cambios de contraseña de cuentas se
registran como eventos de auditoría con actor, acción, recurso, resultado y fecha, en un almacén
solo-anexado con retención de 5 años.
**Cómo se verifica:** criterios `CA` de la sección "Auditoría" de cada spec que toque esos datos,
con test que comprueba que el evento se escribe; `/review` lo verifica.
**Por qué:** trazabilidad exigible por NOM-024 para el expediente clínico electrónico; hoy no se
registra nada.

### P8. Correlación por petición y logs sin datos sensibles
**Regla:** cada petición lleva un identificador `X-Request-Id` (aceptado si es válido o generado)
que se devuelve en la respuesta y aparece en el contexto de todos sus logs y eventos de auditoría.
Ningún log contiene datos personales, de salud, contraseñas, tokens ni valores enlazados de SQL.
Nivel por defecto `info` en staging y producción; `APP_DEBUG=false` fuera de local.
**Cómo se verifica:** test del middleware de correlación; revisión de cada llamada a `Log::`;
checklist de despliegue en `docs/deployment.md`.
**Por qué:** hoy no hay ni un log propio ni correlación; una `QueryException` puede volcar datos
clínicos en `laravel.log`.

### P9. La interfaz usa solo el sistema de diseño
**Regla:** en pantallas y estilos nuevos o modificados, colores, tipografía, espaciados, radios,
sombras y duraciones salen de los tokens de [docs/design/system.md](design/system.md); ningún valor
literal nuevo en estilos, `style=""` ni componentes. Un valor nuevo se añade primero al sistema.
Esas pantallas cumplen **WCAG 2.1 AA** (contraste ≥ 4.5:1 en texto y ≥ 3:1 en bordes de control y
foco, foco visible, estados con texto y no solo color). El código anterior que no lo cumple no
bloquea (sección "Código previo"): es deuda `DS` de `system.md` y se corrige al tocar la pantalla.
**Cómo se verifica:** `/review` revisa las vistas y el CSS tocados (sin literales ni estilos en línea
nuevos) y el contraste de los pares que usan, contra `docs/design/system.html`; el lint automático
de valores literales queda pendiente (DS4).
**Por qué:** mantiene la coherencia entre specs sin revisar cada pantalla a mano, y la app guarda
datos de salud de pacientes que deben poder usarla (decisión del usuario, 2026-10-02).

### P10. Las rutas indexables son descubribles
**Regla:** cada ruta que [docs/seo.md](seo.md) declara indexable (hoy solo `/registro`) llega en el
HTML del servidor con su contenido principal, sin depender de JavaScript; tiene `<title>` y
`<meta name="description">` únicos entre rutas y una sola `<link rel="canonical">` absoluta
construida desde `seo.site_url`; y obtiene **Lighthouse SEO ≥ 90** con como mucho **100 kB de
JavaScript transferido** por ruta (valor propuesto en el upgrade a AI-DD 1.13.0; se ajusta con una
enmienda PATCH). `noindex` solo en las rutas no indexables (inicio de sesión, 404, salud y todo lo
que exige sesión) y nunca en una indexable de producción. El código anterior que no lo cumple no
bloquea (sección "Código previo"): son las brechas `SE` de `docs/seo.md`.
**Cómo se verifica:** test sobre el HTML que devuelve la ruta (título, descripción, canónica,
`noindex`, sin depender de JS) en las specs que crean o cambian rutas públicas, con su sección
«Descubribilidad» en el plan; Lighthouse sobre la build servida en local en `/review` de esas specs;
`/release` comprueba en producción `robots.txt`, `sitemap.xml` y la ausencia de `noindex`.
**Por qué:** el registro es la única puerta pública para los pacientes y debe encontrarse en los
buscadores sin indexar lo privado (decisión del usuario, 2026-10-16).

## Restricciones
- Proyecto académico en etapa MVP; todos los datos son ficticios y el repositorio es privado
  (confirmado por el usuario, 2026-09-28).
- Marco de referencia normativo: LFPDPPP (datos personales) y NOM-024-SSA3 (expediente clínico
  electrónico); conservación de referencia del expediente según NOM-004-SSA3 (5 años).
- Sin presupuesto: solo herramientas y servicios gratuitos u open source.
- Entrega académica aproximada: fin de semestre, diciembre de 2026.
- Stack fijo salvo ADR: Laravel 12, PHP ≥ 8.2, MariaDB, Blade renderizado en servidor, Docker Compose.

## Definición de terminado
- [ ] `php artisan test` pasa completo contra la base `dentissa_test`.
- [ ] `vendor/bin/pint --test` limpio sobre los archivos tocados por la spec.
- [ ] Spec, CHANGELOG y documentos afectados (`docs/*.md`) actualizados.
- [ ] `python .ai/bin/aidd.py validate` sin errores.

## Gobierno
- Cambiar esta constitución requiere subir `version` (SemVer: MAJOR elimina o redefine un
  principio, MINOR añade uno, PATCH aclara redacción) y registrar la enmienda abajo.
- Ante conflicto, la constitución prevalece sobre specs, planes y `AGENTS.md`.
- Independientemente de los principios, ningún despliegue a producción se hace sin aprobación
  humana explícita del responsable del proyecto (`.ai/project.yaml → deploy.prod_approval`).

## Enmiendas
| Versión | Fecha | Cambio | Motivo |
|---|---|---|---|
| 1.0.0 | 2026-09-28 | Versión inicial | /init |
| 1.1.0 | 2026-10-02 | Añade P9: la interfaz usa solo el sistema de diseño y cumple WCAG 2.1 AA en pantallas nuevas o modificadas | Upgrade a AI-DD 1.11.0, sistema de diseño extraído (decisión del usuario, 2026-10-02) |
| 1.2.0 | 2026-10-02 | P2 distingue los cambios de comportamiento (test que falla antes) de los criterios que ya se cumplen (test de caracterización comprobado rompiendo el código) | Tercera excepción a P2 (specs 024, 025 y 027); pendiente del roadmap (decisión del usuario, 2026-10-02) |
| 1.2.1 | 2026-10-03 | P2 aclara que los criterios que no describen comportamiento de la aplicación (no regresión, contenido de un diff, datos externos o comprobación manual) se verifican con el comando o la comprobación que declara el plan | Excepción repetida a P2 en las specs de dependencias (024 y 029) (decisión del usuario, 2026-10-03) |
| 1.3.0 | 2026-10-16 | Añade P10: las rutas indexables son descubribles (HTML del servidor, título y descripción únicos, canónica absoluta, `noindex` solo en lo no indexable, Lighthouse SEO ≥ 90 y ≤ 100 kB de JS por ruta) | Upgrade a AI-DD 1.13.0 con SEO técnico: el usuario decide indexar `/registro` (decisión del usuario, 2026-10-16) |
