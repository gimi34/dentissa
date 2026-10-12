# Dentissa

Sistema web de gestión para una clínica dental: agenda de citas, pacientes, expedientes clínicos,
personal y catálogo de servicios, con cuatro roles (admin, dentista, asistente y paciente).
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
docker compose exec app php artisan test                             # tests  # ⚠ no verificado en /init: Docker no estaba en marcha
docker compose exec app vendor/bin/pint --test <archivos tocados>    # lint (solo lo que tocas)  # ⚠ no verificado en /init: Docker no estaba en marcha
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

Usa `/clarify` para cerrar ambigüedades de una spec antes de aprobarla. Estado de todas las specs:
`python .ai/bin/aidd.py status`.

No implementes sin spec aprobada. No hagas commit sin que pasen los tests y Pint sobre lo tocado.
Nunca despliegues a producción sin aprobación explícita; sigue [docs/deployment.md](docs/deployment.md).
Nunca escribas secretos en el código, los logs ni los docs.

**AI-DLC está en pausa** (decisión del usuario, 2026-09-28): no uses `/aidlc` ni sus skills
`aidlc-*`. Su texto y su estado están en [AIDLC.md](AIDLC.md) y `aidlc/`; no los edites.

## Reglas y estilo
- Dominio en español: tablas, columnas, rutas, vistas, mensajes y comentarios (`citas`,
  `creado_en`, `/citas/crear`). PKs UUID. Tablas en plural snake_case; modelos en singular (`Usuario`).
- Controladores `<Plural>Controller`; métodos CRUD en inglés y helpers en español camelCase.
- Rutas en `routes/web.php`, agrupadas por rol con `middleware(['auth','role:X'])->prefix('X')->name('X.')`.
- Vistas en `resources/views/<modulo>/` en kebab-case, un solo layout `layouts/app.blade.php`.
- Código nuevo: validación en servidor (Form Requests preferidos), autorización por Policy, SQL
  con bindings, `{{ }}`/`@js()` en Blade (principios P3 y P4).
- Tests: PHPUnit, nombres `test_snake_case_en_español`, `DatabaseTransactions` (nunca
  `RefreshDatabase`) contra `dentissa_test`.
- Una spec que toque `resources/views/layouts/app.blade.php`, `public/js/menu.js` o
  `public/css/movil.css` ejecuta `node docs/design/capturar.mjs --medir` (procedimiento en su
  cabecera) en la verificación final de `/implement` y en `/review`: es el único test del menú del
  móvil en el navegador y la CI no lo ejecuta (spec 033, /review R2).
- Commits: Conventional Commits en español (`feat:`, `fix:`, `docs:`…), ramas `feature/NNN-slug` con PR a `main`.

## Mapa del repositorio
| Ruta | Contenido |
|---|---|
| `app/Http/Controllers/` | 13 controladores con toda la lógica (ver docs/architecture.md) |
| `app/Http/Middleware/` | `RoleMiddleware` (rol), `PreventBackHistory` (no-cache) |
| `app/Models/` | `Usuario` (auth), `Servicio`; `User` es un resto sin uso |
| `routes/web.php` | todas las rutas |
| `resources/views/` | vistas Blade por módulo |
| `public/css/` | CSS real de la aplicación |
| `database/dentissa.sql` | volcado con el esquema del dominio y datos ficticios |
| `database/migrations/` | infraestructura + 3 migraciones de julio 2026 |
| `database/testing/` | arnés de la BD de tests |
| `tests/Feature/` | tests de usuarios, servicios y redirección |
| `docker/`, `Dockerfile`, `docker-compose.yml` | entorno local |
| `docs/` | documentación del flujo AI-DD |
| `.ai/` | configuración del flujo y validador |
| `aidlc/`, `AIDLC.md` | metodología AI-DLC en pausa (no editar) |

## Documentación
- [Constitución](docs/constitution.md) — principios innegociables
- [Arquitectura](docs/architecture.md)
- [Despliegue](docs/deployment.md) — entornos, deploy y rollback
- [Seguridad](docs/security.md) — datos sensibles, auth, herramientas y excepciones
- [Observabilidad](docs/observability.md) — logs, correlación, auditoría, métricas y alertas
- [Sistema de diseño](docs/design/system.md) — tokens, componentes, WCAG 2.1 AA y deuda DS (vista: [system.html](docs/design/system.html))
- [Roadmap](docs/roadmap.md) — etapa actual y objetivos
- [Specs](docs/specs/README.md)
- [ADRs](docs/adr/)
- [Plantillas](docs/templates/)
