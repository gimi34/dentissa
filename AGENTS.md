# Dentissa

Sistema web de gestión para una clínica dental: agenda de citas, pacientes, expedientes clínicos,
personal y catálogo de servicios, con cuatro roles (admin, dentista, asistente y paciente, que en el código es `cliente`).
Proyecto académico en etapa MVP, con datos ficticios; entrega prevista a fin de semestre (dic 2026).

> Antes de cualquier cambio lee [docs/constitution.md](docs/constitution.md). Si algo aquí
> contradice la constitución, prevalece la constitución.

## Stack
PHP ^8.2 (Docker: 8.4-fpm) · Laravel 12 + Socialite (Google OAuth) · Blade renderizado en servidor
con CSS propio en `public/css/` · MariaDB 10.11 · Composer (+ npm/Vite, casi sin uso) · Docker
Compose · tipo: `fullstack`

## Comandos
```bash
docker compose up -d && docker compose exec app composer install   # instalar
docker compose exec app php artisan migrate                          # aplicar migraciones sobre el volcado
bash database/testing/bootstrap-test-db.sh                           # preparar la BD de tests (dentissa_test)
docker compose exec -e DB_DATABASE=dentissa_test app php artisan migrate --force   # migraciones del dominio en la BD de tests
docker compose exec app php artisan test                             # tests (también en la CI de cada PR)
docker compose exec app vendor/bin/pint --test <archivos tocados>    # lint (solo lo que tocas; también en la CI)
npm run build                                                        # build de assets (solo welcome.blade.php lo usa)
```
App en http://localhost:8000 · phpMyAdmin en http://localhost:8080 (solo local).
Ejecuta PHP siempre dentro del contenedor: el `composer.lock` exige PHP ≥ 8.4.1 y con un PHP 8.2
local la app no arranca (RD7 en docs/deployment.md).

## Flujo de trabajo
1. `/specify` → spec en `docs/specs/NNN-<slug>/spec.md`
2. `/plan` → `plan.md` con Constitution Check
3. `/tasks` → `tasks.md`
4. `/analyze` → verificación independiente de consistencia → `analysis.md`
5. `/implement` → solo tareas aprobadas, una a la vez, con tests
6. `/review` → contra spec, plan y constitución → `review.md`
7. `/release` → versión, changelog, staging y, con tu aprobación, producción

Trabajo que no añade comportamiento (carriles ligeros, ficha en `docs/changes/`): `/fix` para un
bug (`--hotfix` en producción), `/refactor` para deuda sin cambio de comportamiento, `/chore` para
dependencias, configuración y docs. Si el cambio crece o toca contratos, datos o seguridad, pasa
al flujo completo. `/sync` concilia lo que se hizo fuera del flujo.

Usa `/clarify` para cerrar ambigüedades de una spec antes de aprobarla. Estado de todas las specs:
`python .ai/bin/aidd.py status`.

No implementes sin spec aprobada o ficha abierta de un carril. No hagas commit sin que pasen los tests y Pint sobre lo tocado.
Nunca despliegues a producción sin aprobación explícita; sigue [docs/deployment.md](docs/deployment.md).
Nunca escribas secretos en el código, los logs ni los docs.

**AI-DLC está en pausa** (decisión del usuario, 2026-09-28): no uses `/aidlc` ni sus skills
`aidlc-*`. Su texto y su estado están en [AIDLC.md](AIDLC.md) y `aidlc/`; no los edites.

## Reglas y estilo
- Dominio en español: tablas, columnas, rutas, vistas, mensajes y comentarios (`citas`,
  `creado_en`, `/citas/crear`). PKs UUID. Tablas en plural snake_case; modelos en singular (`Usuario`).
- Controladores `<Plural>Controller` (salvo los de acceso, `Dashboard`, `Perfil` y `UsuarioController`);
  métodos CRUD en inglés y helpers en español camelCase.
- Rutas en `routes/web.php`, agrupadas por rol con `middleware(['auth','role:X'])->prefix('X')->name('X.')`.
  El grupo `cliente` solo lleva `auth` y pone `role:cliente` ruta a ruta (RS2.a en docs/security.md).
- Vistas en `resources/views/<modulo>/` en kebab-case, con dos layouts: `layouts/app.blade.php` para la
  app y `layouts/publico.blade.php` para las páginas públicas (`/`, `/servicios`; spec 038, con
  `public/css/publico.css` y `public/js/publico.js` con `defer`; la página está completa sin él). El
  acceso sigue con su propio HTML.
- Código nuevo: validación en servidor con `$request->validate()` y las reglas en la clase de
  dominio (`reglasDeEntrada()` en `app/Agenda/`) o en un helper del controlador; autorización con
  Gates sobre clases de `app/Autorizacion/` registrados en `AppServiceProvider` (ADR 0003); SQL con
  bindings, `{{ }}`/`@js()` en Blade (principios P3 y P4) (decisión del usuario, 2026-10-18).
- Tests: PHPUnit, nombres `test_snake_case_en_español`, `DatabaseTransactions` (nunca
  `RefreshDatabase`) contra `dentissa_test`.
- Una spec que toque `resources/views/layouts/app.blade.php`, `public/js/menu.js` o
  `public/css/movil.css` ejecuta `node docs/design/capturar.mjs --medir` (procedimiento en su
  cabecera) en la verificación final de `/implement` y en `/review`: es el único test del menú del
  móvil en el navegador y la CI no lo ejecuta (spec 033, /review R2).
- Commits: Conventional Commits en español con la spec como ámbito y la tarea al principio
  (`feat(034): T037 …`, `fix(034): T045 … — R1`); versiones con `chore(release): vX.Y.Z`.
- Ramas: `feature/NNN-slug` para cada spec y `chore/<tema>` para cierres (`chore/cierre-NNN`),
  actualizaciones del flujo (`chore/ai-dd-X.Y.Z`) y mantenimiento; todas con PR a `main` (decisión del usuario, 2026-10-18).

## Mapa del repositorio
| Ruta | Contenido |
|---|---|
| `app/Http/Controllers/` | 13 controladores (más el `Controller` base; `PaginasPublicasController`, spec 038) con casi toda la lógica (ver docs/architecture.md) |
| `app/Agenda/` | servicios de la agenda: reserva, estados, reprogramación y horario (specs 025–028) |
| `app/Autorizacion/` | alcance del personal y Gates de citas y de personal (ADR 0003) |
| `app/Auditoria/` | registro de auditoría: `Auditoria::registrar()` y `denegar()`, almacén, registro central de denegaciones (spec 020, ADR 0004) |
| `app/Listeners/` | fallos de acceso al canal `seguridad` (spec 032) y actor del `Context` de los logs (spec 020) |
| `app/Rules/`, `app/Console/Commands/` | regla `HoraFinPosterior`; comando `dentissa:rotar-credenciales` |
| `app/Http/Middleware/` | `RoleMiddleware` (rol), `PreventBackHistory` (no-cache) |
| `app/Models/` | `Usuario` (auth), `Servicio`; `User` es un resto sin uso |
| `routes/web.php` | todas las rutas |
| `resources/views/` | vistas Blade por módulo |
| `public/css/`, `public/js/` | CSS real de la aplicación (`publico.css`, páginas públicas, spec 038); `menu.js` y `filtros.js` (specs 033 y 034) |
| `public/fonts/` | `newsreader/` y `montserrat/` (páginas públicas, spec 038) y `roboto/` (spec 020), autoalojadas, cada una con su `OFL.txt` |
| `database/dentissa.sql` | volcado con el esquema del dominio y datos ficticios |
| `database/migrations/` | infraestructura + 3 migraciones de julio y 2 de octubre de 2026 (specs 026 y 028) |
| `database/testing/` | arnés de la BD de tests |
| `tests/Feature/` | tests por área (citas, horarios, producción, móvil…); traits en `Concerns/` |
| `tests/js/` | tests de `public/js/publico.js` con `node --test tests/js/publico.test.mjs` (spec 038) |
| `docker/`, `Dockerfile`, `docker-compose.yml` | entorno local |
| `docker/prod/`, `docker-compose.prod.yml`, `deploy/` | producción: imágenes, Compose y scripts que se ejecutan por SSH |
| `.github/workflows/ai-dd.yml` | CI: validador, tests, Pint y escáneres de seguridad |
| `docs/` | documentación del flujo AI-DD |
| `.ai/` | configuración del flujo y validador |
| `aidlc/`, `AIDLC.md` | metodología AI-DLC en pausa (no editar) |

## Documentación
- [Constitución](docs/constitution.md) — principios innegociables
- [Arquitectura](docs/architecture.md)
- [Despliegue](docs/deployment.md) — entornos, deploy y rollback
- [Seguridad](docs/security.md) — datos sensibles, auth, herramientas y excepciones
- [Observabilidad](docs/observability.md) — logs, correlación, auditoría, métricas y alertas
- [SEO técnico](docs/seo.md) — rutas indexables (`/registro`, `/` y `/servicios`), metadatos, sitemap y brechas SE
- [Sistema de diseño](docs/design/system.md) — tokens, componentes, WCAG 2.1 AA y deuda DS (vista: [system.html](docs/design/system.html))
- [Roadmap](docs/roadmap.md) — etapa actual y objetivos
- [Specs](docs/specs/README.md)
- [ADRs](docs/adr/)
- [Plantillas](docs/templates/)
