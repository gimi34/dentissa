# Pila tecnológica — Dentissa

**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` (`main`, 2026-09-02)
**Método**: las versiones que siguen son las **versiones resueltas** leídas de `composer.lock` y `package-lock.json`, no los rangos declarados en los manifiestos.

---

## 1. Corrección de la pila declarada

El estado del flujo de trabajo registraba la pila como **«Vite»** y el sistema de construcción como **«npm (package.json)»**. Ambas cosas son **incorrectas** y quedan corregidas aquí.

| Registro previo | Realidad verificada |
| --- | --- |
| Framework: `Vite` | **`laravel/framework` `v12.62.0`** sobre PHP `^8.2` |
| Sistema de construcción: `npm (package.json)` | **Composer (autoritativo)**, más npm y Vite en estado inerte |
| Base de datos: no registrada | **MariaDB** — `10.4.32` en el origen del volcado, `10.11` en la imagen de contenedor |

**Por qué se produjo el error.** Vite `8.0.1`, Tailwind `4.2.2` y axios `1.13.6` están instalados, declarados en `package.json` y configurados en `vite.config.js`. Pero la directiva `@vite` aparece **exactamente una vez** en las 37 plantillas Blade, en `resources/views/welcome.blade.php`, y **ninguna ruta renderiza esa vista** — la ruta raíz siempre redirige. El estilo real de la aplicación son 1.072 líneas de CSS artesanal servidas desde `public/css/`. Una detección automática que se fija en `package.json` y en `vite.config.js` sin comprobar si la salida se consume concluye «Vite».

---

## 2. Lenguajes

| Lenguaje | Versión | Dónde | Volumen |
| --- | --- | --- | --- |
| **PHP** | `^8.2` declarada en `composer.json`; `8.4` en la imagen de contenedor; `8.2.12` en el entorno que generó el volcado | `app/`, `routes/`, `config/`, `database/`, `public/index.php` | 45 archivos de código |
| **Blade** (plantillas PHP) | Motor de Laravel 12 | `resources/views/` | 37 plantillas, 5.851 líneas |
| **SQL** (dialecto MariaDB) | — | `database/dentissa.sql` | 671 líneas |
| **CSS** | Escrito a mano, sin preprocesador | `public/css/` | 1.072 líneas |
| **JavaScript** | Vanilla, en línea en las plantillas | 8 plantillas con bloques `script` | Sin cuantificar |

**Incoherencia de plataforma PHP.** El `Dockerfile` fija `php:8.4-fpm`, `composer.json` declara `php: ^8.2` y el volcado se generó con PHP `8.2.12`. No es un conflicto de resolución de dependencias — `8.4` satisface `^8.2` — pero significa que **el entorno de ejecución y el de desarrollo no están fijados a la misma versión menor**, lo que puede producir diferencias de comportamiento no reproducibles.

---

## 3. Backend — dependencias de producción

Las cuatro declaradas en `composer.json` bajo `require`, con sus versiones resueltas, más las transitivas relevantes.

| Paquete | Versión resuelta | Rango declarado | Propósito | Criticidad |
| --- | --- | --- | --- | --- |
| `laravel/framework` | `v12.62.0` | `^12.0` | Framework MVC completo: enrutado, Eloquent, Blade, validación, sesiones, colas, autenticación | **Núcleo** |
| `laravel/socialite` | `v5.28.0` | `^5.28` | OAuth con Google — el único proveedor configurado | **Alta**: sostiene todo el registro y acceso federado |
| `laravel/tinker` | `v3.0.2` | `^3.0` | REPL de desarrollo | Baja |
| `nesbot/carbon` | `3.13.0` | Transitiva | Aritmética de fechas | **Alta**: es la pieza central de la regla de agenda (`isoWeekday()`, `addMinutes()`, comparaciones de solape) |
| `guzzlehttp/guzzle` | `7.13.2` | Transitiva | Cliente HTTP que Socialite usa para el intercambio de tokens con Google | Media |
| `league/oauth1-client` | `v1.11.0` | Transitiva | Dependencia de Socialite | Baja |

**Total resuelto**: 325 paquetes en `composer.lock`, contando todas las transitivas de producción y de desarrollo.

**Observación**: `nesbot/carbon` es una dependencia **transitiva** que el código usa **directamente** e intensivamente. No está declarada en `composer.json`. Es una fragilidad menor pero real: una futura versión de Laravel que cambiara su dependencia de fechas rompería el código de agenda sin previo aviso. Declararla explícitamente sería lo correcto.

---

## 4. Backend — herramientas de desarrollo

| Paquete | Versión resuelta | Rango declarado | Estado real |
| --- | --- | --- | --- |
| `phpunit/phpunit` | `12.5.30` | `^12.5.12` | Configurado en `phpunit.xml`, pero **la única prueba de integración falla hoy** |
| `laravel/pint` | `v1.29.3` | `^1.27` | **Instalado y nunca ejecutado.** No hay `pint.json` ni ningún guion que lo invoque |
| `mockery/mockery` | `1.6.12` | `^1.6` | Instalado, **sin ningún uso en el código** |
| `nunomaduro/collision` | `v8.9.4` | `^8.6` | Informes de error en consola |
| `fakerphp/faker` | `v1.24.1` | `^1.23` | Usado sólo por `UserFactory`, que es código muerto |
| `laravel/pail` | `v1.2.7` | `^1.2.5` | Visor de logs en vivo |

**Ausencias notables**: no hay PHPStan, Psalm ni Larastan; no hay Pest ni Laravel Dusk; no hay ninguna extensión de cobertura (Xdebug o PCOV) declarada.

---

## 5. Frontend

### 5.1 Dependencias declaradas — todas en `devDependencies`

| Paquete | Versión resuelta | Rango declarado | Estado real |
| --- | --- | --- | --- |
| `vite` | `8.0.1` | `^8.0.0` | **Inerte** — su salida no la consume ninguna vista alcanzable |
| `tailwindcss` | `4.2.2` | `^4.0.0` | **Inerte** — sólo se aplica a `welcome.blade.php` |
| `@tailwindcss/vite` | `4.2.2` | `^4.0.0` | **Inerte** |
| `laravel-vite-plugin` | `3.0.0` | `^3.0.0` | **Inerte** — integra `@vite`, que aparece una sola vez |
| `axios` | `1.13.6` | `^1.11.0` | **Inerte** — registrado como `window.axios`, **jamás invocado** desde ninguna plantilla |
| `concurrently` | `9.2.1` | `^9.0.1` | **En uso** — es la única del bloque que sí se ejecuta, dentro del guion `composer dev` |

Cinco de las seis dependencias de npm no aportan nada al sistema en ejecución.

### 5.2 Frontend real — sin gestor de paquetes

| Elemento | Detalle |
| --- | --- |
| **CSS** | 1.072 líneas artesanales en `public/css/app.css`, `auth.css` y `citas.css`. El layout las enlaza con `asset()` y una cadena de invalidación calculada por `filemtime()` en cada render |
| **Tipografía** | `Roboto` cargada desde `fonts.googleapis.com` en el layout — **dependencia externa en tiempo de ejecución**, no versionada y fuera del control del despliegue |
| **Iconografía** | SVG en línea dentro de las plantillas; sin librería de iconos |
| **JavaScript** | Vanilla en línea en 8 plantillas: alternar visibilidad de contraseña, asistente de registro en dos pasos, modales de confirmación de borrado, cálculo de duración de servicio y activación de días de horario |

---

## 6. Persistencia

| Elemento | Valor | Evidencia |
| --- | --- | --- |
| **Motor** | MariaDB | `mariadb:10.11` en `docker-compose.yml`; el volcado se generó contra `10.4.32` |
| **Conexión por defecto de la aplicación** | `mysql` según `.env.example` | `DB_CONNECTION=mysql` |
| **Conexión por defecto del framework** | **`sqlite`** | `config/database.php` resuelve `env('DB_CONNECTION', 'sqlite')` |
| **Modo estricto** | Activado | `'strict' => true` en las conexiones `mysql` y `mariadb` |
| **Cotejamiento** | `utf8mb4_unicode_ci`, motor InnoDB | Declarado en las 17 tablas del volcado |
| **Claves primarias** | `char(36)` con `DEFAULT uuid()` | Todas las tablas de dominio |
| **Objetos con lógica** | 1 trigger (`trg_historial_cita`), 2 vistas SQL sin consumidor | `database/dentissa.sql` |
| **Almacenamiento de sesión** | `file` según `.env.example`; **`database` por defecto del framework** | `config/session.php` resuelve `env('SESSION_DRIVER', 'database')` |
| **Caché** | `database` | `.env.example` |
| **Colas** | `database` — configurada, **sin ningún job definido** | `.env.example` |

**Dos trampas de configuración por defecto.** Ambas están hoy enmascaradas por `.env.example`, y ambas se activarían en un despliegue que no copiara ese archivo:

1. **Motor de base de datos**: sin `DB_CONNECTION`, Laravel usa SQLite, que no contiene el esquema de dominio.
2. **Almacenamiento de sesión**: sin `SESSION_DRIVER`, Laravel escribe en la tabla `sessions`, cuya columna `user_id` es un entero heredado del andamiaje, mientras que `usuarios.id` es un UUID de 36 caracteres. Con el modo estricto activado, esa escritura falla. Ver `code-quality-assessment.md`, hallazgo `TD-4`.

---

## 7. Infraestructura y entorno

| Componente | Versión / imagen | Puerto | Observación |
| --- | --- | --- | --- |
| **Aplicación** | `php:8.4-fpm` | `9000` interno | Extensiones instaladas: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip` |
| **Servidor web** | `nginx:alpine` | `8000` publicado | Raíz en `public/`, caché de un año para estáticos, `gzip_static` activado |
| **Base de datos** | `mariadb:10.11` | `3306` **publicado al anfitrión** | Permite contraseña vacía; el volumen se inicializa con el volcado |
| **Administración** | `phpmyadmin:latest` | `8080` publicado | **Etiqueta flotante: no reproducible** |
| **Composer** | `composer:latest` (etapa de construcción) | — | También etiqueta flotante |

**Ausencia total de entornos superiores**: no hay definición de staging ni de producción en ninguna parte del repositorio. `.env.example` fija `APP_ENV=local` y `APP_DEBUG=true`.

---

## 8. Configuración de idioma

`.env.example` declara `APP_LOCALE=en` y `APP_FALLBACK_LOCALE=en`, es decir, **la localización del framework está en inglés** mientras toda la interfaz está en español. La contradicción se resuelve de la forma más rígida posible: **todos los mensajes de validación están escritos a mano en español dentro de cada llamada a `validate()`**, en lugar de usar archivos de idioma. No existe el directorio `lang/`.

La consecuencia práctica es que los mensajes que el equipo escribió salen en español, pero cualquier mensaje que Laravel genere por su cuenta —los de las reglas sin mensaje personalizado, los de error del framework— sale en inglés. Es una inconsistencia visible para el usuario final y de corrección barata: publicar los archivos de idioma en español y fijar `APP_LOCALE=es`.

---

## 9. Sistema de construcción

Hay **dos cadenas**, y sólo una es autoritativa.

### 9.1 Composer — autoritativa

| Guion | Qué hace | Estado |
| --- | --- | --- |
| `composer setup` | Encadena `composer install`, copia de `.env.example` a `.env`, `artisan key:generate`, `artisan migrate --force`, `npm install` y `npm run build` | **FALLA contra una base de datos vacía.** `artisan migrate` aborta al crear `asistente_dentista`, porque declara claves foráneas contra `personal`, que ninguna migración crea |
| `composer dev` | Levanta tres procesos en paralelo con `concurrently`: `php artisan serve`, `php artisan queue:listen --tries=1` y `npm run dev` | Funciona; el proceso de Vite no aporta nada |
| `composer test` | `artisan config:clear` seguido de `artisan test` | Funciona, pero **la suite está en rojo** |

### 9.2 npm con Vite — prácticamente inerte

| Guion | Qué hace | Estado |
| --- | --- | --- |
| `npm run build` | `vite build`, escribe en `public/build` (ignorado por git) | Produce artefactos que nada consume |
| `npm run dev` | Arranca el servidor de desarrollo de Vite | Idem |

### 9.3 Construcción de la imagen Docker

`composer install --no-scripts --no-autoloader --ignore-platform-reqs` sobre los manifiestos, copia del proyecto y `composer dump-autoload --optimize`. **La imagen no ejecuta `npm run build`**, lo que confirma que la cadena de assets no es necesaria para que el sistema funcione.

### 9.4 El único camino de arranque que funciona

`docker compose up`, y funciona por una secuencia no documentada: MariaDB ejecuta el volcado al inicializar el volumen, el volcado incluye la tabla `migrations` **ya poblada** con las tres migraciones de andamiaje, y por eso un `artisan migrate` posterior las salta y aplica sólo las tres de julio de 2026. Ver `architecture.md`, sección 6.

---

## 10. Resumen ejecutivo de la pila

```
Capa                Tecnología                        Versión        Estado
──────────────────────────────────────────────────────────────────────────
Lenguaje            PHP                               ^8.2 / 8.4     Activo
Framework           laravel/framework                 v12.62.0       Activo
Plantillas          Blade                             (Laravel 12)   Activo
Fechas              nesbot/carbon                     3.13.0         Activo, crítico
OAuth               laravel/socialite                 v5.28.0        Activo
Cliente HTTP        guzzlehttp/guzzle                 7.13.2         Activo (vía Socialite)
Base de datos       MariaDB                           10.4.32 / 10.11 Activo
Servidor web        nginx                             alpine         Activo
Estilo              CSS artesanal en public/css       —              Activo
Tipografía          Google Fonts (Roboto)             —              Externa, en ejecución
──────────────────────────────────────────────────────────────────────────
Pruebas             phpunit/phpunit                   12.5.30        Configurado, en rojo
Formateo            laravel/pint                      v1.29.3        Instalado, nunca ejecutado
Dobles de prueba    mockery/mockery                   1.6.12         Instalado, sin uso
──────────────────────────────────────────────────────────────────────────
Empaquetado         vite                              8.0.1          INERTE
CSS utilitario      tailwindcss                       4.2.2          INERTE
Cliente HTTP JS     axios                             1.13.6         INERTE
Integración Blade   laravel-vite-plugin               3.0.0          INERTE
Orquestación local  concurrently                      9.2.1          Activo
──────────────────────────────────────────────────────────────────────────
Análisis estático   —                                 —              AUSENTE
Integración continua —                                —              AUSENTE
Cobertura           —                                 —              AUSENTE
```

---

## Sources

- `composer.json` y `composer.lock` — versiones resueltas extraídas paquete por paquete; 325 entradas
- `package.json` y `package-lock.json` — versiones resueltas extraídas
- `vite.config.js`, `phpunit.xml`
- `Dockerfile` (imagen base y extensiones PHP), `docker-compose.yml` (imágenes y puertos), `docker/nginx/dentissa.conf`
- `config/database.php` (conexión por defecto, modo estricto), `config/session.php` (resolución del `driver`), `config/auth.php`, `config/services.php`
- `.env.example` — plantilla versionada; el archivo `.env` **no** se abrió
- `database/dentissa.sql` — cabecera del volcado con versiones de phpMyAdmin, MariaDB y PHP de origen
- `resources/views/` — recuento de apariciones de `@vite` en las 37 plantillas
- Escaneo del desarrollador: `aidlc/spaces/default/intents/260902-docs-sistema-dental/inception/reverse-engineering/developer-scan.md`
