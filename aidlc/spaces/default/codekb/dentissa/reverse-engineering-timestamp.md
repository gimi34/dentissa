# Registro de la ingeniería inversa — Dentissa

## Cuándo se realizó

| Campo | Valor |
| --- | --- |
| **Fecha del análisis** | 2026-09-02 |
| **Repositorio** | `dentissa` — repositorio único en la raíz del proyecto, sin submódulos ni monorepo |
| **Commit analizado** | `9fcff4c635d5134d9f252b6158812c4dd15e2657` |
| **Fecha del commit** | 2026-09-02 12:33:57 -0600 |
| **Autor del commit** | `gimi34` |
| **Rama** | `main` |
| **Tipo de escaneo** | Primer escaneo — rescan completo, sin almacén previo que fusionar |
| **Profundidad** | Comprehensive |
| **Intención activa** | `260902-docs-sistema-dental` |

## Estado del árbol de trabajo

El árbol estaba **limpio para todo el código de aplicación** en el momento del análisis: `app/`, `resources/`, `routes/`, `database/`, `config/`, `public/`, `composer.json` y `package.json` no tenían cambios sin confirmar. Los únicos archivos modificados eran `.gitignore`, `README.md` y los directorios del propio flujo de trabajo (`.claude/`, `aidlc/`), ninguno de los cuales forma parte del sistema dental.

Por tanto, **los nueve artefactos describen exactamente el estado del commit indicado**.

## Contexto del repositorio

| Métrica | Valor |
| --- | --- |
| Archivos versionados | 132 |
| Archivos `.php` versionados | 82 — 45 de código y 37 plantillas Blade |
| Líneas en `app/` | 2.545 |
| Líneas en `resources/views/` | 5.851 |
| Líneas en `public/css/` | 1.072 |
| Líneas en `database/dentissa.sql` | 671 |
| Historia | 25 commits, 4 autores |
| Archivo con más rotación | `routes/web.php`, tocado en 7 commits |

Nota sobre el recuento: en disco hay 7.859 archivos `.php` porque `vendor/` está instalado pero **no versionado**. Cualquier medida automática que no excluya ese directorio dará una imagen falsa del tamaño del proyecto.

## Qué produjo este análisis

Nueve artefactos, todos escritos desde este único escaneo:

| Artefacto | Contenido principal |
| --- | --- |
| `business-overview.md` | Dominio, 4 actores, 8 capacidades, 16 reglas de negocio con identificador, capacidades declaradas no implementadas y estado de la línea base de datos |
| `architecture.md` | Estilo arquitectónico, vista de despliegue, vista de componentes, **modelo de datos completo**, **8 diagramas de interacción**, el mecanismo de arranque del esquema y **10 ADR retrospectivos** |
| `code-structure.md` | Organización del árbol, clasificación de archivos, patrones y anti-patrones, código muerto y convenciones de nomenclatura |
| `api-documentation.md` | Las **89 rutas** enumeradas una a una, contratos de formulario de las operaciones críticas y la integración OAuth saliente |
| `component-inventory.md` | **23 componentes** con responsabilidad, dependencias y valoración de salud |
| `technology-stack.md` | Versiones resueltas de los lockfiles y corrección de la pila declarada |
| `dependencies.md` | Dependencias externas, **matriz de acoplamiento controlador-tabla** y cadena de arranque |
| `code-quality-assessment.md` | 3 defectos funcionales, 3 hallazgos de seguridad, 20 señales de deuda y plan de remediación priorizado |
| `reverse-engineering-timestamp.md` | Este documento |

## Correcciones a hipótesis previas

Dos suposiciones de partida resultaron **falsas** y están corregidas en todos los artefactos:

1. **La pila no es «Vite».** Es Laravel `v12.62.0` sobre PHP `^8.2` con MariaDB. Vite, Tailwind y axios están instalados pero inertes.
2. **El esquema no vive únicamente en una base de datos en ejecución.** Está versionado en `database/dentissa.sql` y montado como script de inicialización. Lo que sí es cierto es que ese volcado está desfasado respecto al código: el esquema efectivo es «el volcado más las tres migraciones de julio de 2026».

Además, este análisis **corrigió una cifra del escaneo de partida**: el recuento de rutas de `routes/web.php` es de **89 rutas registradas**, no 61. La cifra se verificó por dos caminos independientes — el recuento mecánico de los 82 registros de verbo único más las 7 que genera la única llamada a `Route::resource`, y la enumeración manual grupo por grupo, que coincide exactamente.

## Hallazgos nuevos aportados por esta síntesis

Además de sintetizar el escaneo, esta fase verificó y añadió tres hallazgos:

- **`TD-20`** — La línea base no siembra **ninguna** fila de `horarios_dentista`, de modo que tras un arranque limpio **no puede agendarse ninguna cita**. Tampoco siembra ningún usuario con rol `asistente`.
- **`TD-19`** — Entre la comprobación de solape y la inserción de una cita no hay transacción ni bloqueo, lo que deja una ventana de concurrencia real que viola la regla `BRN-6`.
- **`DEF-3`** — `Route::resource` registra la ruta de ficha de servicios, pero `ServiciosController` no tiene el método correspondiente ni existe su plantilla.

## Advertencia sobre datos sensibles

El repositorio contiene datos personales sensibles y credenciales, tanto en `database/dentissa.sql` como al final de `README.md`, y ambos están en la historia de git. **Ninguno de los nueve artefactos reproduce ningún valor**: se nombra el archivo y el tipo de dato, nunca el dato. El archivo `.env` no se abrió en ningún momento. El detalle y el plan de remediación están en `code-quality-assessment.md`, hallazgo `SEC-1`.

## Limitaciones de este análisis

Lo que **no** se verificó línea a línea, y sobre lo que por tanto ningún artefacto hace afirmaciones:

- Las **30 plantillas Blade** no leídas íntegramente. Se verificaron las 37 mediante un barrido estructural exhaustivo —rutas generadas, formularios con su método, `@csrf`, bloques de script leídos completos, búsqueda de salida sin escapar y de llamadas asíncronas— pero **no se afirma nada sobre maquetación, accesibilidad ni consistencia visual**.
- Los **6 archivos de `config/`** que son los valores por defecto de Laravel 12. Se inventariaron; no puede afirmarse que estén intactos.
- Las **1.072 líneas de `public/css/`**. Se verificó su existencia, su tamaño y que el layout las carga; el detalle de las reglas no se analizó.
- `vendor/` y `node_modules/`. Las versiones se leyeron de los lockfiles.

Si una fase posterior necesita afirmaciones sobre la capa visual, requiere una segunda pasada sobre esos tres conjuntos.

---

## Scope of Analysis

```yaml
scope_version: 1
kind: full
intent: 260902-docs-sistema-dental
fingerprint: 7e3623f50f95066fe7975af115970687af9336ad
analyzed:
  paths:
    - ./
    - app/
    - app/Http/Controllers/
    - app/Http/Middleware/
    - app/Models/
    - app/Providers/
    - bootstrap/
    - routes/
    - database/dentissa.sql
    - database/migrations/
    - database/seeders/
    - database/factories/
    - tests/
    - docker/
    - public/index.php
    - resources/views/layouts/app.blade.php
    - resources/views/partials/badge-estado.blade.php
    - resources/views/placeholder.blade.php
    - resources/views/pacientes/edit.blade.php
    - resources/views/personal/edit.blade.php
    - resources/views/usuarios/edit.blade.php
    - resources/js/
    - resources/css/
    - config/auth.php
    - config/services.php
    - config/database.php
    - config/session.php
    - composer.json
    - composer.lock
    - package.json
    - package-lock.json
    - vite.config.js
    - phpunit.xml
    - Dockerfile
    - docker-compose.yml
    - .env.example
    - .editorconfig
    - .dockerignore
    - .gitignore
    - .gitattributes
    - README.md
  components:
    - AuthController
    - GoogleAuthController
    - RegisterController
    - RoleMiddleware
    - PreventBackHistory
    - CitasController
    - ClienteCitasController
    - PersonalController
    - PacientesController
    - ExpedientesController
    - PerfilController
    - DashboardController
    - UsuarioController
    - ServiciosController
    - Modelos Eloquent
    - database/dentissa.sql
    - database/migrations
    - routes/web.php
    - resources/views
    - public/css
    - Cadena de assets Vite
    - Infraestructura Docker
    - tests
shallow:
  paths:
    - config/app.php
    - config/cache.php
    - config/filesystems.php
    - config/logging.php
    - config/mail.php
    - config/queue.php
    - public/css/
    - public/images/
    - public/.htaccess
    - public/robots.txt
    - storage/
    - artisan
    - vendor/
    - node_modules/
```

**Nota sobre `resources/views/`.** Las 37 plantillas se verificaron por barrido estructural completo y 6 se leyeron íntegras; esas 6 figuran en `analyzed.paths` y el componente `resources/views` figura en `analyzed.components`. Las 30 restantes no se leyeron línea a línea: la limitación queda registrada en la sección «Limitaciones de este análisis» de este mismo documento y repetida en `component-inventory.md` y en `code-quality-assessment.md`, de modo que ninguna fase posterior tome como verificada una afirmación sobre su maquetación o su accesibilidad.
