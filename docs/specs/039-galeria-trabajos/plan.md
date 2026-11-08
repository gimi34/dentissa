---
spec: 039-galeria-trabajos
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-07
---

# Plan · 039 Galería de trabajos

## Enfoque técnico
Una tabla nueva, `galeria_fotos`, guarda cada foto: descripción, posición, si aparece un paciente,
quién declaró el consentimiento y cuándo, y el tamaño del archivo publicado.

Al subir una foto, la clase `App\Galeria\ProcesadorFoto` hace todo el tratamiento con GD:
- la decodifica y la endereza;
- la recorta a 4:3 y la reduce;
- la vuelve a codificar en dos WebP: uno de 1600 px como mucho (500 kB o menos) y otro de 800 px (60 kB o
  menos, salvo imágenes parecidas al ruido), que se sirven con `srcset`;
- los guarda en el disco `galeria` del volumen `storage`, nunca el original (ADR 0009).

Las páginas públicas (`/`, `/galeria`) y la ruta que sirve cada foto se resuelven en
`PaginasPublicasController`. La gestión vive en `FotosGaleriaController`, dentro del grupo `admin`,
con `role:admin` y el Gate `gestionar-galeria`. Cada publicación, movimiento y baja deja un evento en
el registro de auditoría de la spec 020, y los 403 ya los registra el registro central.

La interfaz construye el candidato `c3` aprobado (`design.md`):
- la sección «Nuestros trabajos» en `publico/inicio.blade.php`;
- `/galeria` sobre la estructura de `/servicios`;
- la pantalla de gestión dentro de `.formulario-accesible`;
- el carrusel como mejora progresiva dentro de `public/js/publico.js`.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec `approved` (2026-11-07, con CA16) y `design.md` aprobado; `tasks.md` se aprobará antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA y cada TM tiene su test en Trazabilidad. Todos los criterios cambian comportamiento (rutas, tabla y vistas nuevas) salvo CA3, que se caracteriza sobre la página actual y se comprueba rompiendo la condición de la sección | test: los de Trazabilidad; CA3 con `comprobado rompiendo` en su tarea |
| P3 Autorización en el servidor, por rol y por recurso | ✅ | Las rutas de gestión van en el grupo `admin` (`role:admin`) y cada acción llama a `Gate::authorize('gestionar-galeria')`, con `App\Autorizacion\GestionGaleria` (ADR 0003). El recurso (la foto) es de la clínica, no de un usuario: el alcance es el rol. Ningún control vive solo en la vista | test: `GestionGaleriaAccesoTest` (cada acción × dentista, asistente y paciente → 403; visitante → redirección al inicio de sesión). El caso permitido del admin se prueba en `SubidaFotoGaleriaTest` (`store`) y en `GestionGaleriaTest` (`index`, `subir`, `bajar`, `quitar` y `destroy`) (/analyze A18) |
| P4 Entrada validada y SQL con bindings | ✅ | `$request->validate()` con reglas en `FotosGaleriaController::reglasDeSubida()`: `file`, `mimes:jpg,jpeg,png,webp` (por contenido), `max:5120`, `dimensions:max_width=6000,max_height=6000`, `descripcion` de 1 a 160 caracteres, `con_paciente` `in:si,no` y `consentimiento` `accepted_if:con_paciente,si`. El parámetro `{foto}` es UUID (`whereUuid`) y se resuelve con Eloquent. En Blade solo la salida escapada (dobles llaves), sin `{!! !!}` | test: `SubidaFotoGaleriaTest` (cada regla) · lint: semgrep sobre los archivos tocados |
| P5 Nada sensible en el repositorio | ✅ | Las imágenes de los tests se generan con GD en el propio test, incluida la que lleva EXIF y GPS sintéticos (CA14). No se versiona ninguna foto | test: gitleaks en `/implement` y en la CI · manual: revisión del diff de `tests/` |
| P6 Ninguna vulnerabilidad crítica o alta sin excepción | ✅ | Ninguna dependencia de Composer ni de npm nueva. Los paquetes del sistema `libjpeg62-turbo-dev` y `libwebp-dev` se revisan con `trivy image` sobre las imágenes `app` de local y de producción, antes y después del cambio: un aviso alto o crítico nuevo se corrige o lo registra el usuario como excepción en `docs/security.md`. EX2 no los cubre: es una lista cerrada (/analyze A3). Línea base del 2026-11-07: `composer audit` limpio con EX4 (vence el 2026-11-11, antes del `/release` de esta spec, que necesitará el `/chore` de Symfony 7.4.23, condición de T096) y `npm audit` con 0 | test: `trivy image` en la tarea del Dockerfile y en la verificación final; `composer audit`, `npm audit` y `trivy fs` en `/review` y `/release` |
| P7 Auditoría de accesos y cambios a datos sensibles | ✅ | CA16: `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada` con `Auditoria::registrar()` en modo crítico, dentro de la transacción; el 403 lo registra `RegistroCentralDenegaciones` (spec 020) | test: `AuditoriaGaleriaTest` |
| P8 Correlación y logs sin datos sensibles | ✅ | Dos logs nuevos (Observabilidad), los dos `warning`, con `request_id` del `Context` y sin nombre ni ruta de archivo ni descripción: `galeria.procesado_fallido` (tipo detectado y tamaño) y `galeria.archivo_no_borrado` (UUID de la foto) (/analyze A2). Los eventos de auditoría no llevan la descripción | test: `SubidaFotoGaleriaTest::test_un_fallo_al_procesar_no_publica_nada_y_registra_el_aviso`, `GestionGaleriaTest::test_si_no_se_puede_borrar_el_archivo_queda_el_aviso` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | La primera tarea aplica a `system.md` (1.12.0) y `system.html` los «Cambios a incorporar al sistema» de `design.md` (`--foto-ratio`, `--carrusel-foto-w`, `--thumb-w` y los componentes). Las pantallas usan solo tokens. WCAG 2.1 AA medido con `evaluate.mjs` y `design/criterios.json` | test: `GaleriaCssTest` (sin literales en las reglas nuevas de `publico.css` y `app.css`, `.pub-solo-lector` incluida; tokens nuevos en `system.md`); `PaginasPublicasCssTest::VISTAS` con `publico/galeria.blade.php`, `publico/_trabajo.blade.php` y `galeria/index.blade.php` (sin `style=""` ni `<style>`; /analyze A4) · manual: `evaluate.mjs` con `design/medir.mjs` sobre la build, y `node docs/design/capturar.mjs --medir` (toca `layouts/app.blade.php`) |
| P10 Las rutas indexables son descubribles | ✅ | `/galeria` es indexable: contenido en el HTML del servidor, título y descripción propios, una canónica absoluta desde `seo.site_url`, sin `noindex` y ≤ 100 kB de JavaScript. Ver Descubribilidad | test: `PaginaGaleriaTest::test_metadatos_de_galeria`, `PaginasPublicasTest` (título y descripción únicos con `/galeria`) · manual: Lighthouse SEO ≥ 90 en `/review` |

Conteo: 10 cumplen, ninguno no aplica y ninguno se viola.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `Dockerfile` (ruta protegida, aprobado por el usuario el 2026-11-07) y `docker/prod/Dockerfile` | `libjpeg62-turbo-dev` y `libwebp-dev`; `docker-php-ext-configure gd --with-jpeg --with-webp` antes de instalar `gd` (ADR 0009) | Medio: trivy puede marcar los paquetes nuevos; hay que reconstruir el contenedor de desarrollo |
| `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` | `client_max_body_size 6m;` en el `server` (hoy rige 1 MB) | Bajo |
| `config/filesystems.php` | Disco `galeria`: `local`, raíz `storage_path('app/galeria')`, sin URL pública | Bajo |
| `database/migrations/2026_11_07_000000_create_galeria_fotos_table.php` (nuevo) | Tabla `galeria_fotos` (Contratos y datos); solo *expand* | Bajo |
| `app/Models/GaleriaFoto.php` (nuevo) | Modelo con UUID, `creado_en` y `actualizado_en`, orden por `posicion`, relación con la cuenta que declaró el consentimiento | Bajo |
| `app/Galeria/ProcesadorFoto.php` (nuevo) | `procesar(UploadedFile): FotoProcesada` (bytes de los dos WebP, ancho y alto del grande). Pasos, en este orden para acotar la memoria (D9): decodificar; calcular el rectángulo 4:3 centrado con las medidas ya giradas según la orientación EXIF; recortar y reducir en un solo `imagecopyresampled` a 1600 × 1200 como mucho (1200 × 1600 si la orientación es 5, 6, 7 u 8, porque aún no está girada; /analyze A28); liberar la imagen original; girar o voltear la reducida; derivar de ella la de 800 × 600 como mucho. Codifica el grande con calidad 82, 72, 62 y 52; si a 52 pesa más de 500 kB, reduce las medidas a 0,8 veces (manteniendo 4:3) y repite, así que el tope es estricto. El pequeño, de `min(800, ancho)` px, con 82, 72, 62, 52, 42, 32 y 22 hasta 60 kB o menos; si ni con 22 llega (solo pasa con imágenes parecidas al ruido), se queda con el de calidad 22 (D15). Lanza `FotoNoProcesable` si GD no la puede leer | Medio: memoria con fotos grandes (pico de unos 160 MB con 6000 × 6000, con `memory_limit` 256M) |
| `app/Galeria/Galeria.php` (nuevo) | Operaciones con transacción: `publicar(...)` (límite de 12 con `lockForUpdate`, posición al final, consentimiento, los dos archivos y auditoría), `mover(foto, ±1)` (intercambia con la vecina) y `quitar(foto)` (borra la fila, compacta posiciones, borra los dos archivos después del *commit* y, si un borrado falla, deja el log `galeria.archivo_no_borrado`) | Medio: archivos y fila tienen que quedar coherentes si algo falla |
| `app/Autorizacion/GestionGaleria.php` (nuevo) y `app/Providers/AppServiceProvider.php` | Gate `gestionar-galeria` (`rol === 'admin'`), registrado como los demás (ADR 0003). La 014 también toca `AppServiceProvider`: solo se añade una línea | Bajo |
| `app/Http/Controllers/FotosGaleriaController.php` (nuevo) | `index` (lista, formulario y, con `{foto}` en la ruta de quitar, el diálogo abierto), `store`, `subir`, `bajar`, `confirmarQuitar`, `destroy`; `reglasDeSubida()` y los mensajes de CA11 y CA12 | Medio |
| `app/Http/Controllers/PaginasPublicasController.php` | `inicio` pasa `fotos`; nuevos `galeria()` y `foto(Request, GaleriaFoto, int $ancho)`: sirve el WebP del tamaño pedido como una respuesta normal con el contenido leído del disco (no `BinaryFileResponse` ni `StreamedResponse`), con las cabeceras de «Entrega de las fotos», y 304 si `If-None-Match` coincide | Bajo |
| `routes/web.php` | Públicas: `GET /galeria` (`galeria.publica`) y `GET /galeria/fotos/{foto}/{ancho}` (`galeria.foto`, `whereUuid('foto')`, `whereIn('ancho', ['800', '1600'])`, `withoutMiddleware(PreventBackHistory::class)` para que no pise su `Cache-Control`; /analyze A1). Grupo `admin`: `GET /admin/galeria`, `POST /admin/galeria`, `PATCH /admin/galeria/{foto}/subir`, `PATCH /admin/galeria/{foto}/bajar`, `GET /admin/galeria/{foto}/quitar`, `DELETE /admin/galeria/{foto}` (`admin.galeria.*`, `whereUuid`) | Bajo |
| `resources/views/publico/inicio.blade.php` | Sección «Nuestros trabajos» después del avance de servicios, solo `@if ($fotos->isNotEmpty())` (CA3) | Bajo: la 040 también añadirá su sección en ese hueco |
| `resources/views/publico/galeria.blade.php` (nuevo) y `resources/views/publico/_trabajo.blade.php` (nuevo) | `/galeria` con el mosaico y su estado vacío; la foto de trabajo compartida (numeral, «Foto n de N», descripción y `img` con `src` de 800 px, `srcset` de 800 y 1600 px, `sizes`, `width`, `height` y `loading`, según «Entrega de las fotos») | Bajo |
| `resources/views/galeria/index.blade.php` (nuevo) | Gestión: tarjetas, formulario «Nueva foto», errores, aviso y diálogo de quitar, en `layouts.app` | Medio: estados del formulario |
| `resources/views/layouts/app.blade.php` | Entrada «Galería» en el menú del admin, después de «Servicios» | Medio: toca el menú del móvil, se mide con `capturar.mjs --medir` |
| `public/css/publico.css` | Carrusel, foto de trabajo, mosaico y galería vacía (de `c3/galeria.css`) | Bajo |
| `public/css/app.css` | Tarjeta de foto, pregunta del paciente, consentimiento, selector de archivo y aviso de error, dentro de `.formulario-accesible` (de `c3/gestion.css`) | Bajo |
| `public/js/publico.js` y `tests/js/publico.test.mjs` | Carrusel (de `c3/galeria.js`) en el mismo script, para seguir con un solo script propio por ruta; la función que elige la foto vecina se exporta para su test | Bajo |
| `docs/design/system.md`, `docs/design/system.html` | Versión 1.12.0 con los cambios de `design.md` | Bajo |
| `deploy/verificar.sh` y `tests/Feature/ConfiguracionProduccionTest.php` | Tres comprobaciones nuevas en producción (D17): GD de `app` con JPEG y WebP (`gd_info()`), `storage/app/galeria` escribible por `www-data` (o su padre `storage/app` si aún no existe) y `client_max_body_size 6m` en la configuración de `web` (`nginx -T`). Van en una sección propia, `== Galería (spec 039)`, que corre con y sin `--interna`. Usan `-u www-data` en `app`, `ok` y `fallo` como el resto, y dejan sin cambios el bloque de configuración de 16 comprobaciones y su test (/analyze A26). El test lee el script, como los de cabeceras | Bajo |
| `tests/Feature/Galeria/*Test.php`, `tests/Unit/Galeria/ProcesadorFotoTest.php` (nuevos), `tests/Feature/PaginasPublicasTest.php`, `tests/Feature/PaginasPublicasCssTest.php` (`VISTAS`) | Ver Estrategia de pruebas | — |

## Contratos y datos
**Tabla `galeria_fotos`** (migración nueva, solo *expand*; la versión anterior la ignora):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `char(36)` PK (UUID) | los archivos del disco `galeria` son `<id>-1600.webp` y `<id>-800.webp` (el sufijo es el tamaño de la ruta, no el ancho real). `char(36)` como `usuarios.id` y las demás migraciones: `uuid()` crea en MariaDB 10.11 una columna nativa incompatible con esas claves foráneas (/analyze A8) |
| `descripcion` | `varchar(160)` | texto plano; es el `alt` y el pie |
| `posicion` | `smallint unsigned` | 1…N, sin huecos; índice único |
| `con_paciente` | `boolean` | |
| `consentimiento_usuario_id` | `char(36)` null, FK `usuarios.id` `nullOnDelete` | solo si `con_paciente` |
| `consentimiento_nombre` | `varchar(201)` null | nombre y apellido de quien declaró, copiados al publicar: la declaración se conserva aunque la cuenta cambie |
| `consentimiento_en` | `timestamp` null | |
| `ancho`, `alto` | `smallint unsigned` | medidas del WebP grande, para `width` y `height` del `img` y su descriptor en `srcset`; el pequeño mide `min(800, ancho)` con la misma proporción |
| `bytes` | `int unsigned` | del grande, ≤ 512 000 |
| `bytes_pequena` | `int unsigned` | del pequeño; ≤ 61 440 salvo imágenes parecidas al ruido (D15) |
| `creado_en`, `actualizado_en` | `timestamp` | |

**Rutas:**

| Método y ruta | Nombre | Quién | Respuesta |
|---|---|---|---|
| `GET /galeria` | `galeria.publica` | todos (CA7: igual con sesión) | 200 con el mosaico o el estado vacío |
| `GET /galeria/fotos/{foto}/{ancho}` (`ancho`: `800` o `1600`) | `galeria.foto` | todos | 200 `image/webp` con las cabeceras de «Entrega de las fotos» · 304 sin cuerpo si `If-None-Match` coincide con el `ETag` · 404 si la foto no existe (también si se acaba de quitar), si `{foto}` no es un UUID, si `{ancho}` es otro valor o si la fila existe pero falta su archivo en el disco (volumen perdido, D4), en este último caso sin 500 ni log con la ruta (/analyze A27) |
| `GET /admin/galeria` | `admin.galeria.index` | admin | 200 |
| `POST /admin/galeria` | `admin.galeria.store` | admin | 302 a la lista con «Foto publicada.» · 302 atrás con errores (CA11, CA12), sin guardar nada |
| `PATCH /admin/galeria/{foto}/subir` y `/bajar` | `admin.galeria.subir`, `admin.galeria.bajar` | admin | 302 a la lista; en el extremo, sin cambios |
| `GET /admin/galeria/{foto}/quitar` | `admin.galeria.quitar` | admin | 200: la lista con el diálogo «¿Quitar esta foto?» abierto (funciona sin JavaScript) |
| `DELETE /admin/galeria/{foto}` | `admin.galeria.destroy` | admin | 302 a la lista con «Foto quitada.» |

Dentista, asistente o paciente en cualquier ruta `admin.galeria.*`: 403 (`role:admin`), registrado
por la spec 020. Sin sesión: 302 al inicio de sesión.

**Entrega de las fotos** (decisiones del usuario, 2026-11-08, /analyze A1, A6, A7 y A10):
- Cabeceras de cada 200:
  - `Content-Type: image/webp`, `X-Content-Type-Options: nosniff`;
  - `Cache-Control: no-cache, private`: el navegador revalida en cada visita (304 si sigue igual,
    404 en cuanto se quita, así que CA10 se cumple al instante), y ninguna caché compartida la
    guarda. Symfony añade `private` a un `no-cache` solo, así que ese es el valor exacto que sale
    (/analyze A21);
  - `ETag: "<id>-<ancho>"`, porque el contenido de un archivo nunca cambia;
  - `X-Robots-Tag: noindex` solo si `con_paciente`.
- La ruta va sin `PreventBackHistory`. Así no pisa `Cache-Control`, y su `withHeaders()` no se
  aplica a la respuesta.
- `img` de cada foto: `src` de 800, `srcset="<800> <min(800, ancho)>w, <1600> <ancho>w"`, `width`
  y `height` del grande. `sizes` por sitio:
  - carrusel de `/`: `(min-width: 900px) 53vw, (min-width: 560px) 72vw, 86vw`;
  - primera foto del mosaico: `(min-width: 1200px) 800px, (min-width: 900px) 66vw, (min-width: 560px) 50vw, 100vw` (por debajo de 900 px ya no ocupa dos columnas: `c3/galeria.css`, /analyze A22);
  - resto del mosaico: `(min-width: 900px) 33vw, (min-width: 560px) 50vw, 100vw`.

  Los cortes son los de `c3/galeria.css`. Con densidad 1 y hasta 1440 px, todas las fotos piden la
  pequeña; con pantallas de más densidad, el navegador elige la grande.
- Carga diferida: en `/`, todas las fotos del carrusel llevan `loading="lazy"` (la sección queda muy
  por debajo de la primera pantalla). En `/galeria`, las tres primeras cargan sin esperar y las
  demás con `lazy`.
- Peso de la primera visita (K9, ≤ 1 000 000 B). A 1440 px, Chromium carga las fotos `lazy` que
  quedan a menos de unos 1250 px de la ventana, es decir, las 12 de `/galeria`. Con 12 pequeñas de
  60 kB o menos más unos 183 kB del resto de la página, son unos 920 kB. Prueba de concepto con
  libwebp del 2026-11-08: una foto con grano realista pesa 166 kB a 1600 px y 44 kB a 800 px con
  calidad 82.

**Mensajes** (CA11 literales; los de CA12 dicen el motivo):
- CA11: «Escribe una descripción de la foto.», «Indica si en la foto aparece un paciente.» y «Marca
  que tienes el consentimiento escrito del paciente.»
- Sin archivo: «Elige la foto que quieres publicar.»
- CA12:
  - no es una imagen, o es de otro formato: «El archivo no es una imagen JPG, PNG o WebP.»;
  - más de 5 MB: «La foto pesa más de 5 MB.»;
  - más de 6000 px: «La foto es demasiado grande: como mucho 6000 píxeles por lado.»;
  - 12 fotos: «Ya hay 12 fotos. Quita una antes de subir otra.»;
  - GD no puede leerla: «No se pudo leer la foto. Prueba con otro archivo.»
- Descripción de más de 160 caracteres: «La descripción puede tener como mucho 160 caracteres.»

**Pantallas** (`design.md`, candidato `c3`):

| Pantalla | Estados | Accesibilidad |
|---|---|---|
| Inicio, sección «Nuestros trabajos» | con fotos / sin fotos (no se pinta) | `h2`; ventana tabulable con `role="region"` y nombre; «Anterior» y «Siguiente» como enlaces a la foto vecina; región `role="status"` montada vacía y escrita al cambiar; numeral `aria-hidden`; sin avance automático |
| `/galeria` | con fotos / vacía | un `h1`; `ol` de `figure` con `figcaption`; `alt` = descripción |
| Gestión | lista, formulario, errores, consentimiento, diálogo de quitar, avisos de éxito | `h1` «Galería de trabajos», errores junto a cada campo con `aria-invalid` y `aria-describedby`, aviso `role="alert"`, `fieldset` con `legend`, radios y casilla de 44 px, diálogo `role="dialog"` con `aria-modal` y el foco en «Cancelar» |

**Qué ve cada rol:** solo el admin ve la entrada «Galería» del menú y sus pantallas. El resto de
roles y los visitantes ven `/` (con sesión, `/` sigue llevando a su panel) y `/galeria` igual. No
cambia ningún texto que prometa acciones a otros roles.

## Estrategia de pruebas
PHPUnit con `DatabaseTransactions` contra `dentissa_test` y `Storage::fake('galeria')`. Las imágenes
se generan con GD en el test (`UploadedFile::fake()->image()` y un helper que inserta un segmento
APP1 con EXIF y GPS sintéticos en un JPEG).
- **Unit** `ProcesadorFotoTest`:
  - el resultado son dos WebP 4:3: el grande de 1600 × 1200 como mucho y el pequeño de `min(800, ancho)`;
  - el grande pesa 500 kB o menos también con una imagen de ruido de 6000 × 4500 (se reducen las
    medidas);
  - el pequeño de una foto con grano realista pesa 60 kB o menos;
  - no contiene `Exif`, `GPS` ni `XMP`;
  - se endereza según la orientación (6 y 8 giran; 3 voltea);
  - con 6000 × 6000 y orientación 6, el pico de memoria del procesado
    (`memory_reset_peak_usage()` antes, `memory_get_peak_usage()` después) queda por debajo de
    200 MB, con margen frente a `memory_limit` 256M (/analyze A5);
  - un archivo que no decodifica lanza la excepción.
- **Feature** `SubidaFotoGaleriaTest` (CA8, CA11, CA12), `GestionGaleriaTest` (CA9 y CA10),
  `GestionGaleriaAccesoTest` (CA13), `PaginaGaleriaTest` (CA2, CA6, CA7, CA14 en la descarga, CA15),
  `CarruselInicioTest` (CA1, CA3 y CA4 en el HTML) y `AuditoriaGaleriaTest` (CA16).
- **CSS** `GaleriaCssTest` sobre `publico.css` y `app.css`, con el trait `ReglasCss`: sin literales,
  solo tokens, movimiento solo bajo `prefers-reduced-motion: no-preference`.
- **JS** `node --test tests/js/publico.test.mjs`: la foto vecina (de la primera a la última y al
  revés) y que la región de estado no lee el numeral.
- **Navegador** (`verificación manual:` en las tareas de pantalla): `design/medir.mjs` con
  `evaluate.mjs` y `design/criterios.json` sobre la build servida en local (puertas y K1–K13; K11 del
  inicio queda como en la página actual, ver Riesgos). Además, `capturar.mjs --medir` del menú del
  móvil, la revisión visual contra `design/candidatos/c3/` a 320, 390 y 1440 px, y Lighthouse SEO
  ≥ 90 en `/galeria`.
- **Datos de medición** (/analyze A13): `medir.mjs` mide la app con la base `dentissa`. El script
  `.ai/inspect/039-fotos.php` (no versionado, en `.gitignore`) se ejecuta con
  `php artisan tinker` dentro del contenedor, en dos modos:
  - `publicar`: con `Galeria::publicar`, sube las seis fotos con las descripciones de `criterios.json`.
  - `publicar 12`: además, seis fotos más con grano realista, hasta llegar a 12, para medir K9 en su
    caso límite en `/` y `/galeria` (/analyze A23).
    Son imágenes con grano realista generadas con GD; la 2 lleva paciente, con el consentimiento
    declarado por la cuenta de admin de prueba.
  - `retirar`: las quita con `Galeria::quitar`.

  Se corre `publicar` antes de `medir.mjs` y `retirar` después. Al final, `galeria_fotos` está
  vacía y `storage/app/galeria` no tiene archivos. Los eventos de auditoría de esas operaciones
  quedan en la base local, que tiene datos ficticios.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un dentista, asistente o paciente gestiona la galería con peticiones a mano | A01:2025 | rutas `admin.galeria.*` | `role:admin` en el grupo y `Gate::authorize('gestionar-galeria')` en cada acción; 403 registrado | `GestionGaleriaAccesoTest` (cada acción × rol → 403 y la galería sin cambios; sin sesión → inicio de sesión) |
| TM2 | Tampering: un ejecutable o un políglota PHP disfrazado de `.jpg` | A05:2025 · A08:2025 | subida y entrega | tipo por contenido (`mimes` con finfo) y decodificación con GD. Se guardan solo los píxeles recodificados en WebP, con el UUID como nombre, fuera de `public/`; se sirven como `image/webp` con `nosniff` | `SubidaFotoGaleriaTest::test_un_archivo_que_no_es_imagen_aunque_acabe_en_jpg_se_rechaza` · `ProcesadorFotoTest::test_el_resultado_es_webp_recodificado` · `PaginaGaleriaTest::test_la_foto_se_sirve_como_webp_con_nosniff` |
| TM3 | DoS: archivos enormes, bombas de descompresión o llenar el disco | A06:2025 | subida | 6 MB en nginx, 5 MB en la validación, como mucho 6000 px por lado antes de decodificar (`dimensions` lee la cabecera), procesado con el pico de memoria acotado (D9), 12 fotos con `lockForUpdate`, sin conservar originales | `SubidaFotoGaleriaTest` (más de 5 MB, más de 6000 px, la número 13) · `ProcesadorFotoTest` (6000 × 6000 con orientación 6, pico < 200 MB) |
| TM4 | Information disclosure: ubicación u otros datos de la cámara en la foto publicada | A02:2025 | procesado | GD no copia metadatos al recodificar; se aplica la orientación y no se guarda el original | `ProcesadorFotoTest::test_no_quedan_datos_de_la_camara` · `PaginaGaleriaTest::test_la_foto_descargada_no_tiene_exif` (CA14) |
| TM5 | XSS almacenado por la descripción | A05:2025 | vistas públicas y gestión | salida escapada de Blade en el pie y en `alt`; el JS escribe con `textContent` | `PaginaGaleriaTest::test_la_descripcion_se_muestra_como_texto` (CA15) · `CarruselInicioTest` (mismo caso en `/`) · `GestionGaleriaTest::test_la_descripcion_se_muestra_como_texto_en_la_gestion` (tarjeta y diálogo de quitar; /analyze A9) |
| TM6 | Path traversal o enumeración en `/galeria/fotos/{foto}` | A01:2025 | entrega | `whereUuid` y `GaleriaFoto` resuelto por Eloquent; la ruta del archivo sale del `id` de la fila | `PaginaGaleriaTest::test_una_direccion_que_no_es_una_foto_responde_404` (`../`, texto, UUID inexistente) |
| TM7 | Repudiation: nadie responde de una foto con paciente publicada | A09:2025 | publicación | declaración con cuenta, nombre copiado y fecha (CA9); evento `galeria_foto_publicada` crítico con `con_paciente` | `GestionGaleriaTest::test_la_declaracion_muestra_quien_y_cuando` · `AuditoriaGaleriaTest` |
| TM8 | Information disclosure: una foto quitada (consentimiento retirado) sigue accesible | A01:2025 | baja y entrega | se borran la fila y los dos archivos; la dirección da 404. `Cache-Control: no-cache, private` con `ETag`: el navegador revalida en cada visita y las cachés compartidas no la guardan, y Cloudflare no la guarda (sin extensión y con `no-cache`; T095 anota `cf-cache-status`). Las fotos con paciente llevan `X-Robots-Tag: noindex`, para que un buscador de imágenes no las conserve (D16) | `GestionGaleriaTest::test_la_foto_quitada_deja_de_abrirse` (CA10: 404 en las dos medidas) · `PaginaGaleriaTest::test_la_foto_se_sirve_con_revalidacion` (cabeceras exactas, 304 con `If-None-Match`) · `PaginaGaleriaTest::test_solo_las_fotos_con_paciente_llevan_noindex` |
| TM9 | Tampering: CSRF en subir, mover o quitar | A01:2025 | formularios de gestión | el middleware CSRF de Laravel, activo en el grupo `web`; cada formulario lleva `@csrf` | `GestionGaleriaTest::test_los_formularios_llevan_token_csrf` (el token en el HTML; Laravel lo desactiva en los tests) |

Casos de abuso de la spec:
- CA12 lo cubren TM2 y TM3;
- CA13 lo cubre TM1;
- CA14 lo cubre TM4;
- CA15 lo cubre TM5;
- el «administrador con prisa» de la spec lo cubren CA9 y CA11 con TM7.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: carrusel en `/` tras los servicios, en orden, con descripción, «Anterior» y «Siguiente», «Foto n de N», sin avance automático y «Ver toda la galería» | `inicio.blade.php`, `_trabajo.blade.php`, `publico.js`, `publico.css` | `CarruselInicioTest::test_el_carrusel_va_despues_de_los_servicios_en_orden` (orden de secciones y de fotos, textos, enlace), `publico.test.mjs` (vecina, sin temporizadores). verificación manual: `medir.mjs` (K1, K2) y comparación con `c3/inicio.html` a 320, 390, 900 y 1440 px |
| CA2: `/galeria` con todas las fotos en orden, descripción y vuelta; vacía con su texto | `PaginasPublicasController::galeria`, `galeria.blade.php` | `PaginaGaleriaTest::test_muestra_todas_en_orden_con_su_descripcion`, `::test_vacia_muestra_el_aviso`. verificación manual: `medir.mjs` (K3, K3b, K4) y comparación con `c3/galeria.html` y `c3/galeria-vacia.html` |
| CA3: sin fotos, `/` sin carrusel ni enlace | `inicio.blade.php` (`@if`) | `CarruselInicioTest::test_sin_fotos_no_hay_carrusel_ni_enlace` (caracterización sobre la página actual: comprobado rompiendo el `@if`) |
| CA4: sin JavaScript, todas las fotos y los enlaces | `inicio.blade.php`, `galeria.blade.php` (todo en el HTML; los controles son enlaces) | `CarruselInicioTest::test_todas_las_fotos_estan_en_el_html` (las seis `img`, `href="#trabajo-n"`). verificación manual: puerta `G-sin-js` de `medir.mjs` |
| CA5: de 320 a 1440 px sin desbordar, controles ≥ 44 px, foco visible, sin animación con movimiento reducido | `publico.css`, `app.css` | `GaleriaCssTest::test_el_movimiento_solo_sin_movimiento_reducido`. verificación manual: puertas `G-desborde`, `G-toque`, `G-foco` y `G-reducido` de `medir.mjs` |
| CA6: título, descripción, canónica y sin `noindex` en `/galeria`; únicos | `galeria.blade.php` | `PaginaGaleriaTest::test_metadatos_de_galeria`, `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` (con `/galeria`) |
| CA7: con sesión de cualquier rol, `/galeria` igual | `routes/web.php` (fuera de los grupos por rol) | `PaginaGaleriaTest::test_con_sesion_se_ve_igual` (dataProvider: los cuatro roles) |
| CA8: subir JPG, PNG o WebP ≤ 5 MB sin paciente → se publica al final con su descripción como `alt` | `FotosGaleriaController::store`, `Galeria::publicar`, `ProcesadorFoto` | `SubidaFotoGaleriaTest::test_publica_cada_formato_al_final` (dataProvider jpg, png, webp; `alt`; posición; archivo en el disco) |
| CA9: con paciente, solo con la casilla; la gestión muestra quién y cuándo | `Galeria::publicar`, `galeria/index.blade.php` | `GestionGaleriaTest::test_con_paciente_y_consentimiento_se_publica`, `::test_la_declaracion_muestra_quien_y_cuando`. verificación manual: `medir.mjs` (K6, K6b) y comparación con `c3/gestion-consentimiento.html` |
| CA10: cambiar el orden o quitar (tras confirmar) se refleja en la siguiente visita; la quitada no se abre | `subir`, `bajar`, `confirmarQuitar`, `destroy`, `Galeria::mover`, `Galeria::quitar` | `GestionGaleriaTest::test_subir_y_bajar_cambian_el_orden`, `::test_quitar_pide_confirmacion`, `::test_la_foto_quitada_deja_de_abrirse` (404 en las dos medidas, también con `If-None-Match`), `PaginaGaleriaTest::test_la_foto_se_sirve_con_revalidacion`. verificación manual: `medir.mjs` (K8) y comparación con `c3/gestion-quitar.html` |
| CA11: sin descripción, sin indicar paciente o sin consentimiento → su mensaje y nada publicado | `reglasDeSubida()`, `galeria/index.blade.php` | `SubidaFotoGaleriaTest::test_cada_falta_tiene_su_mensaje_y_no_publica` (dataProvider con los tres literales; ni fila ni archivo). verificación manual: `medir.mjs` (K7, K7b) |
| CA12 (abuso): no imagen aunque sea `.jpg`, otro formato, > 5 MB o la número 13 → motivo y nada guardado | `reglasDeSubida()`, `Galeria::publicar` | `SubidaFotoGaleriaTest::test_un_archivo_que_no_es_imagen_aunque_acabe_en_jpg_se_rechaza`, `::test_otro_formato_se_rechaza` (GIF), `::test_mas_de_5_mb_se_rechaza`, `::test_mas_de_6000_px_se_rechaza`, `::test_con_12_fotos_no_se_publica_la_siguiente` |
| CA13 (abuso): dentista, asistente, paciente o visitante → negado y sin cambios | `routes/web.php` (grupo `admin`), `GestionGaleria`, `FotosGaleriaController` | `GestionGaleriaAccesoTest::test_otros_roles_reciben_403_en_cada_accion` (dataProvider rol × acción), `::test_sin_sesion_va_al_inicio_de_sesion` |
| CA14 (abuso): la descarga no conserva los datos de la cámara | `ProcesadorFoto` | `ProcesadorFotoTest::test_no_quedan_datos_de_la_camara`, `PaginaGaleriaTest::test_la_foto_descargada_no_tiene_exif` |
| CA15 (abuso): la descripción con etiquetas se muestra como texto | vistas con la salida escapada de Blade, `publico.js` con `textContent` | `PaginaGaleriaTest::test_la_descripcion_se_muestra_como_texto`, `CarruselInicioTest::test_la_descripcion_se_muestra_como_texto` |
| CA16: publicar, mover y quitar quedan registrados (quién, acción, foto, si hay paciente, cuándo, resultado, sin descripción ni imagen); el 403 con sesión, como acceso denegado | `Galeria` (`Auditoria::registrar`, crítico), `RegistroCentralDenegaciones` (existente) | `AuditoriaGaleriaTest::test_publicar_mover_y_quitar_quedan_registrados`, `::test_el_evento_no_lleva_la_descripcion`, `::test_si_la_auditoria_falla_no_se_publica_nada`, `::test_el_acceso_denegado_queda_registrado` |
| RNF: WCAG 2.1 AA; el carrusel no se mueve solo y anuncia la foto | vistas, `publico.js` | `CarruselInicioTest::test_la_region_de_estado_esta_montada_y_vacia`, `publico.test.mjs`. verificación manual: puertas de `medir.mjs` y recorrido con teclado contra `c3` |
| RNF: `/galeria` indexable, Lighthouse SEO ≥ 90, JavaScript ≤ 100 kB, contenido en el HTML del servidor | `galeria.blade.php` | `PaginaGaleriaTest::test_metadatos_de_galeria`, `::test_solo_carga_el_script_publico_con_defer`. verificación manual: K10 de `medir.mjs` y Lighthouse en `/review` |
| RNF: cada foto ≤ 500 kB; las de fuera de pantalla con carga diferida | `ProcesadorFoto`, `_trabajo.blade.php` | `ProcesadorFotoTest::test_el_grande_pesa_como_mucho_500_kb` (también con ruido), `::test_el_pequeno_de_una_foto_realista_pesa_como_mucho_60_kb`, `CarruselInicioTest::test_todas_las_fotos_del_carrusel_cargan_en_diferido`, `PaginaGaleriaTest::test_solo_las_tres_primeras_cargan_sin_esperar`, `::test_cada_foto_ofrece_las_dos_medidas` (`src` de 800, `srcset` con los dos descriptores y `sizes` de Contratos). verificación manual: K9 de `medir.mjs` en `/` y `/galeria` con 12 fotos con grano realista (`publicar 12` de «Datos de medición») |
| Comprobación en producción (spec → Notas para /plan; D17) | `deploy/verificar.sh` | `ConfiguracionProduccionTest::test_verificar_comprueba_lo_que_necesita_la_galeria` (GD con JPEG y WebP, directorio escribible y `client_max_body_size 6m`). verificación manual: `verificar.sh` en verde tras el deploy (T097) |
| TM1 | ver CA13 | `GestionGaleriaAccesoTest` |
| TM2 | `ProcesadorFoto`, `PaginasPublicasController::foto` | ver Modelo de amenazas |
| TM3 | nginx, `reglasDeSubida()`, `Galeria::publicar` | ver CA12 |
| TM4 | `ProcesadorFoto` | ver CA14 |
| TM5 | vistas, `publico.js` | ver CA15 y `GestionGaleriaTest::test_la_descripcion_se_muestra_como_texto_en_la_gestion` |
| TM6 | `routes/web.php` (`whereUuid`), `PaginasPublicasController::foto` | `PaginaGaleriaTest::test_una_direccion_que_no_es_una_foto_responde_404` |
| TM7 | `Galeria::publicar` | ver CA9 y CA16 |
| TM8 | `Galeria::quitar`, `PaginasPublicasController::foto`, `routes/web.php` (sin `PreventBackHistory`) | ver CA10, `PaginaGaleriaTest::test_la_foto_se_sirve_con_revalidacion` y `::test_solo_las_fotos_con_paciente_llevan_noindex` |
| TM9 | `galeria/index.blade.php` | `GestionGaleriaTest::test_los_formularios_llevan_token_csrf` |

## Observabilidad
- **Logs nuevos** (los dos sin nombre ni ruta de archivo, sin descripción y sin actor, que ya está
  en el `Context`):
  - `galeria.procesado_fallido` → `warning` → `request_id` (por el `Context`), `tipo_detectado`,
    `bytes`. Test: `SubidaFotoGaleriaTest::test_un_fallo_al_procesar_no_publica_nada_y_registra_el_aviso`.
  - `galeria.archivo_no_borrado` → `warning` → `request_id`, `foto_id` (el UUID, que ya no
    corresponde a ninguna fila) y `ancho` (`800` o `1600`). Se emite si, después del *commit* de
    `quitar`, el disco no puede borrar un archivo (/analyze A2). Test:
    `GestionGaleriaTest::test_si_no_se_puede_borrar_el_archivo_queda_el_aviso`: el disco falso
    devuelve `false` en los dos borrados, la foto queda quitada y hay exactamente dos logs, uno por
    archivo (`ancho` 800 y 1600), sin la ruta ni la descripción. Regla: un log por archivo que no se
    pueda borrar (/analyze A24).
- **Eventos de auditoría** (`Auditoria::registrar`, crítico, dentro de la transacción de `Galeria`):
  - `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada`;
  - resultado `exito`, `recurso_tipo` `galeria_foto` y `recurso_id` el UUID;
  - detalle: `con_paciente` (bool) y `posicion` (int), más `direccion` (`arriba` o `abajo`) al mover;
  - nunca la descripción ni la imagen.

  El 403 lo registra `RegistroCentralDenegaciones` como `acceso_denegado`, con `accion_solicitada` =
  nombre de la ruta y `recurso_tipo` `galeria`. Tests en `AuditoriaGaleriaTest` (Trazabilidad CA16).
  `docs/observability.md` gana sus filas (T091; /analyze A16).
- **Métricas y alertas:** ninguna nueva. El vigilante (`dentissa:vigilar`) ya avisa de los 5xx y de
  los fallos de auditoría.
- **Cómo se verifica tras el deploy:**
  1. Publicar una foto de prueba sin paciente y comprobar que se ve en `/` y en `/galeria`.
  2. En `/admin/auditoria`, ver `galeria_foto_publicada` con el `request_id` de la petición.
  3. Anotar las cabeceras de `/galeria/fotos/<id>/800` desde fuera: `Cache-Control: no-cache, private`,
     `ETag` y `cf-cache-status` distinto de `HIT`.
  4. Quitar la foto y comprobar que su dirección responde 404.
  5. Revisar `dentissa-error.log` (sin 413) y `laravel.log` (sin `galeria.procesado_fallido` ni
     `galeria.archivo_no_borrado`).
  6. `bash deploy/verificar.sh` en verde, con las comprobaciones de D17.

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/galeria` | sí | «Nuestros trabajos · Dentissa» | «Fotos de tratamientos hechos en la consulta Dentissa en Lázaro Cárdenas.» | `https://dentissa.app/galeria` (`rtrim(config('seo.site_url'))`, como en la 038) | no: el sitemap no existe todavía (objetivo 7, que tendrá que incluir `/galeria`) | — | `PaginaGaleriaTest::test_metadatos_de_galeria`, `PaginasPublicasTest` (únicos) |
| `/` | sí (sin cambios) | sin cambios | sin cambios | sin cambios | — | — | `PaginasPublicasTest` (sigue pasando) |
| `/galeria/fotos/{foto}/{ancho}` | no es HTML: imagen pública; las fotos con paciente con `X-Robots-Tag: noindex` (D16) | — | — | — | no | — | `PaginaGaleriaTest::test_la_foto_se_sirve_como_webp_con_nosniff`, `::test_solo_las_fotos_con_paciente_llevan_noindex` |

- **Renderizado:** el del proyecto (`ssr`, Blade). Todas las fotos y sus descripciones están en el
  HTML; `publico.js` solo mueve el carrusel.
- **Imagen para compartir:** ninguna. Sin `og:*` ni `twitter:*`, como en la 038 (`docs/seo.md` →
  Exclusiones).
- **Brechas `SE`:** no atiende ninguna. `docs/seo.md` gana la fila de `/galeria`, la regla «Se indexa»
  la incluye y la brecha del sitemap (objetivo 7) añade `/galeria` a su lista.

## Rollout
- **Feature flag:** no aplica. Con la galería vacía, `/` no cambia (CA3). `/galeria` muestra el
  estado vacío y la gestión solo la ve el admin. La función se «enciende» cuando el admin sube la
  primera foto.
- **Requisito previo:** el `/chore` de Symfony 7.4.23 liberado y `composer audit` limpio sin EX4,
  que vence el 2026-11-11. Es condición de T096 (/analyze A12).
- **Orden de despliegue:** lo hace `deploy/desplegar.sh vX.Y.Z` en un solo paso:
  1. construye las imágenes, ya con GD con JPEG y WebP y nginx a 6 MB;
  2. ejecuta la migración *expand* `create_galeria_fotos_table`;
  3. levanta la aplicación.

  El directorio `storage/app/galeria` se crea con la primera foto, dentro del volumen `storage`.
  `verificar.sh` comprueba después GD, el directorio (o su padre) y nginx (D17).
- **Compatibilidad:** la versión anterior ignora la tabla nueva y el directorio, así que conviven sin
  problemas durante el despliegue.
- **Rollback:**
  - `bash deploy/volver.sh v0.14.1` (o la versión anterior que esté en producción), sin la copia:
    la migración es compatible (`docs/deployment.md` → Rollback).
  - La tabla y las fotos se quedan y la versión anterior no las muestra. Al volver a desplegar la
    039, siguen ahí.
  - Si hiciera falta retirarlas, antes de `volver.sh` y con la 039 todavía en marcha:
    `php artisan migrate:rollback --step=1` y borrar `storage/app/galeria`.
- **Métricas a vigilar tras el deploy:**
  - 5xx en `/galeria`, `/galeria/fotos/*` y `POST /admin/galeria`;
  - 413 en `dentissa-error.log`;
  - `auditoria.fallo` en el canal `seguridad`;
  - el uso de memoria de `app` al subir una foto grande.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Procesado con GD ampliado (→ ADR 0009, `accepted`).** GD ya está instalado, pero sin JPEG ni
  WebP.
  - Opciones: ampliar GD; Imagick; una librería de Composer (como Intervention Image, que necesita GD
    o Imagick de todas formas).
  - Decisión: ampliar GD en los dos Dockerfiles (decisión del usuario, 2026-11-07). `Dockerfile` es
    una ruta protegida, aprobada en esta misma decisión.
  - Sin dependencias de Composer. Paquetes del sistema de Debian, versiones de la imagen base fijada.
- **D2 · Qué se guarda (→ ADR 0009).** Solo los dos WebP recodificados (D15): recortados al centro a
  4:3, el grande de 1600 × 1200 como mucho y 500 kB o menos, sin metadatos. Nunca el original.
  - Recortar al publicar sigue a `design.md` (`--foto-ratio`) y evita saltos (CLS).
  - Recodificar quita los datos de la cámara (CA14) y deja inservible cualquier carga útil escondida
    (TM2).
- **D3 · Dónde y cómo se sirve (→ ADR 0009).** Disco `galeria` en `storage/app/galeria` (volumen
  `storage`), servido por `GET /galeria/fotos/{foto}`.
  - Opciones: disco `public` con `storage:link` y nginx; servir desde la aplicación.
  - Decisión: desde la aplicación, porque `web` no monta el volumen y son como mucho 12 fotos.
  - Caché (decisión del usuario, 2026-11-08, /analyze A1). Opciones: `public, max-age=3600`
    (descartada: la foto quitada se vería hasta una hora, en contra de CA10), `no-store` (gasta más)
    o revalidar siempre. Decisión: `no-cache` con `ETag`, 304 si no cambió. La ruta va sin
    `PreventBackHistory`, que pisaría la cabecera, y la respuesta lleva el contenido (no un
    `BinaryFileResponse`, en el que fallaría su `withHeaders()`).
  - Sin extensión en la URL y con `no-cache`: Cloudflare no la guarda en caché, y CA10 se cumple al
    borrar.
- **D4 · Copias (decisión del usuario, 2026-11-07).** Las fotos no entran en `respaldar.sh` por ahora.
  Riesgo en `docs/deployment.md` (T091) y deuda en el roadmap (T031) (/analyze A16).
- **D5 · Auditoría con el registro de la 020 (decisión del usuario, 2026-11-07, `/specify --edit`
  CA16).** Eventos en modo crítico dentro de la transacción: si el registro falla, no se publica,
  mueve ni quita nada (como `contrasena_restablecida` en la 041).
- **D6 · Spec junta (decisión del usuario, 2026-11-07).** Se mantienen los 16 criterios en una spec,
  registrado en la spec → Decisiones.
- **D7 · Quitar sin JavaScript.** La confirmación es una página: `GET /admin/galeria/{foto}/quitar`
  devuelve la gestión con el diálogo abierto, como `c3/gestion-quitar.html`, y el formulario `DELETE`
  dentro. Así funciona sin JS y sin el modal con `onclick` del resto del admin.
- **D8 · El carrusel en `publico.js`.** El carrusel va en el script público y no en un archivo
  aparte. `docs/seo.md` y `PaginasPublicasTest::test_solo_carga_el_script_publico_con_defer` fijan un
  solo script propio por ruta. Unos 2,6 kB más.
- **D9 · Límite de 6000 px por lado y memoria acotada.** No lo pide la spec, pero cae dentro de CA12
  (archivo enorme disfrazado). Los móviles guardan por defecto fotos de 12 MP (4000 × 3000).
  - Una foto de 6000 × 6000 son unos 144 MB en GD. Girar o recortar la imagen completa crearía otra
    igual, y con unos 288 MB superaría `memory_limit` 256M (local y producción montan
    `docker/nginx/php/local.ini`). Eso es un error fatal: la subida daría 500 en lugar del mensaje
    de CA12 (/analyze A5).
  - Presupuesto: pico de 200 MB como mucho en el peor caso admitido. Se recorta y reduce en un solo
    `imagecopyresampled` con el rectángulo calculado sobre las medidas giradas. Después se libera
    la original y se gira la reducida, de 1600 × 1200 como mucho (unos 7,7 MB). Pico estimado:
    144 + 7,7 + 7,7 MB más la aplicación. Lo prueba `ProcesadorFotoTest`.
- **D15 · Dos medidas por foto (decisión del usuario, 2026-11-08, /analyze A6 y A7; → ADR 0009).**
  - Opciones: una sola versión de 250 kB como mucho (más pérdida de calidad); aceptar K9 por encima;
    una versión de 800 px servida con `srcset`.
  - Decisión: dos WebP, con `sizes` y carga diferida según «Entrega de las fotos».
    - El grande, de 1600 × 1200 como mucho y 500 kB o menos: el RNF de la spec, estricto, reduciendo
      las medidas si la calidad 52 no basta.
    - El pequeño, de `min(800, ancho)`, con 60 kB o menos como objetivo, para que K9 se cumpla con 12
      fotos.
  - Prueba de concepto con libwebp (Pillow 12.3) el 2026-11-08. Una imagen de ruido de 1600 × 1200
    pesa 740 kB incluso con calidad 22; una foto con grano realista, 166 kB con calidad 82 (y 44 kB
    a 800 px). Por eso el grande necesita reducir medidas, y el pequeño solo supera 60 kB con
    imágenes que no son fotos.
  - Si el pequeño no llega a 60 kB ni con calidad 22, se publica igual, en lugar de rechazar una foto
    válida (CA8). K9 se comprueba con fotos con grano realista.
- **D16 · `noindex` en las fotos con paciente (decisión del usuario, 2026-11-08, /analyze A10).**
  `X-Robots-Tag: noindex` en `/galeria/fotos/*` cuando `con_paciente`. Un buscador de imágenes no
  las guarda ni las sigue mostrando después de retirar el consentimiento. Las demás se pueden
  indexar. Riesgo residual (el buscador puede tardar en procesar la cabecera) en `docs/security.md`
  (T091).
- **D17 · `verificar.sh` (decisión del usuario, 2026-11-08, /analyze A11).** Entra en la 039: GD de
  `app` con JPEG y WebP, `storage/app/galeria` (o `storage/app`) escribible por `www-data` y
  `client_max_body_size 6m` en `web`, en la sección propia de Cambios por módulo (/analyze A26). Atiende la pregunta de las Notas para /plan de la spec.
- **D18 · Identificadores `char(36)` (/analyze A8).** `id` y `consentimiento_usuario_id` como
  `char(36)`, igual que `usuarios.id` y las migraciones existentes.
- **D10 · Descripción de 160 caracteres como mucho.** La spec deja fuera los textos largos por foto;
  160 cabe en dos líneas del pie del carrusel (supuesto).
- **D11 · Textos que la spec no fija.** Los de `design.md` → Decisiones y los mensajes de Contratos
  (supuesto, se confirman al aprobar el plan).
- **D12 · Orden con la 040.** La 039 se implementa primero. La 040 (promociones, `approved` sin plan)
  colocará su sección respecto al carrusel en su propio plan.
- **D13 · Concurrencia del límite de 12.** `lockForUpdate` sobre las filas dentro de la transacción
  de `publicar`: dos subidas a la vez no dejan 13.
- **D14 · K11 del inicio.** El CLS de 0,212 de la página actual queda fuera, para un `/fix` de la 038
  (decisión del usuario en `/design-spec`). `medir.mjs` lo seguirá marcando hasta entonces.

## Impacto en arquitectura
- `docs/architecture.md`:
  - **Módulos:** `app/Galeria/` (`ProcesadorFoto` y `Galeria`).
  - **Datos:** la tabla `galeria_fotos` y el disco `galeria`.
  - **Backend:** las rutas públicas `/galeria` y `/galeria/fotos/{foto}`, y el grupo `admin.galeria.*`
    con su Gate.
  - **Frontend:** `publico/galeria.blade.php`, `galeria/index.blade.php` y el carrusel en
    `publico.js`.
  - **Despliegue:** GD con JPEG y WebP, nginx a 6 MB, las fotos en el volumen `storage` y fuera de
    las copias.
- `docs/deployment.md` (T091): las fotos en el volumen `storage`, el riesgo de no tener copia (RD) y
  `client_max_body_size`.
- `docs/security.md`: las fotos con paciente como datos de salud publicados con consentimiento, con
  `noindex` y su riesgo residual (D16), y la subida de archivos.
- `docs/observability.md`: los eventos de la galería.
- `docs/seo.md`: la fila de `/galeria`.
- `AGENTS.md`, mapa del repositorio (ruta protegida, con aprobación al implementar): `app/Galeria/`.
- ADR 0009 `accepted` (plan aprobado por el usuario el 2026-11-08), con las correcciones de /analyze A1 y A6 (dos medidas, revalidación y
  `noindex`).

## Riesgos y mitigaciones
- **Paquetes nuevos en la imagen (`libjpeg`, `libwebp`) con vulnerabilidades.** `trivy image` sobre
  `app` (local y producción) antes y después de añadirlos; la diferencia queda anotada en la tarea.
  Un aviso alto o crítico nuevo se corrige (versión de la imagen base) o lo registra el usuario como
  excepción en `docs/security.md`. EX2 no los cubre (/analyze A3). `trivy` también en `/review` y
  `/release`.
- **Memoria al decodificar fotos grandes.** Límite de 6000 px, verificado antes de decodificar, y
  orden del procesado de D9. `ProcesadorFotoTest` mide el pico con 6000 × 6000 y orientación 6, y
  tras el deploy se vigila la memoria de `app`.
- **Archivo y fila incoherentes si algo falla.**
  - Publicar: el archivo se escribe dentro de la transacción y se borra si esta falla.
  - Quitar: el archivo se borra después del *commit*. Si ese borrado fallara, queda un archivo
    huérfano que nadie sirve, porque la ruta exige la fila; queda el log `galeria.archivo_no_borrado`.
- **Sin copia de las fotos (D4).** Si se pierde el volumen, el admin vuelve a subirlas. Las
  declaraciones de consentimiento están en la base, que sí se copia. Va al roadmap.
- **Caché.** Con `no-cache`, ni el navegador ni una caché compartida sirven la foto sin preguntar.
  Solo la conserva quien ya la descargó, en su equipo, y eso no se puede evitar. Las fotos con paciente
  llevan además `noindex` (D16).
- **Peso con imágenes que no son fotos.** Si el administrador sube imágenes parecidas al ruido, el
  pequeño pasa de 60 kB y `/galeria` puede superar K9. Se acepta: solo afecta al peso, nunca a la
  publicación (D15).
- **K11 del inicio (CLS de la página actual).** Fuera de la 039 (D14); el `/fix` de la 038 lo
  corrige.
- **Solape con la 040 y la 014.** La 040 añade su sección en el mismo hueco de `inicio.blade.php`, y
  la 014 toca `AppServiceProvider`. Se implementa la 039 primero (D12) y en ese archivo solo se
  añade una línea.
- **EX4 vence el 2026-11-11.** El `/release` de la 039 necesitará antes el `/chore` de Symfony 7.4.23,
  o `composer audit` fallará. Es condición de T096 (Rollout).
