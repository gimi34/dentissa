---
spec: 039-galeria-trabajos
status: approved   # draft | approved | blocked
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
- la vuelve a codificar en WebP de 500 kB o menos;
- la guarda en el disco `galeria` del volumen `storage`, nunca el original (ADR 0009).

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
| P3 Autorización en el servidor, por rol y por recurso | ✅ | Las rutas de gestión van en el grupo `admin` (`role:admin`) y cada acción llama a `Gate::authorize('gestionar-galeria')`, con `App\Autorizacion\GestionGaleria` (ADR 0003). El recurso (la foto) es de la clínica, no de un usuario: el alcance es el rol. Ningún control vive solo en la vista | test: `GestionGaleriaAccesoTest` (cada acción × dentista, asistente y paciente → 403; visitante → redirección al inicio de sesión; admin → permitido) |
| P4 Entrada validada y SQL con bindings | ✅ | `$request->validate()` con reglas en `FotosGaleriaController::reglasDeSubida()`: `file`, `mimes:jpg,jpeg,png,webp` (por contenido), `max:5120`, `dimensions:max_width=6000,max_height=6000`, `descripcion` de 1 a 160 caracteres, `con_paciente` `in:si,no` y `consentimiento` `accepted_if:con_paciente,si`. El parámetro `{foto}` es UUID (`whereUuid`) y se resuelve con Eloquent. En Blade solo la salida escapada (dobles llaves), sin `{!! !!}` | test: `SubidaFotoGaleriaTest` (cada regla) · lint: semgrep sobre los archivos tocados |
| P5 Nada sensible en el repositorio | ✅ | Las imágenes de los tests se generan con GD en el propio test, incluida la que lleva EXIF y GPS sintéticos (CA14). No se versiona ninguna foto | test: gitleaks en `/implement` y en la CI · manual: revisión del diff de `tests/` |
| P6 Ninguna vulnerabilidad crítica o alta sin excepción | ✅ | Ninguna dependencia de Composer ni de npm nueva. Los paquetes del sistema `libjpeg62-turbo-dev` y `libwebp-dev` en las imágenes los revisa `trivy`. Línea base del 2026-11-07: `composer audit` limpio con EX4 (vence el 2026-11-11, antes del `/release` de esta spec, que necesitará el `/chore` de Symfony 7.4.23) y `npm audit` con 0 | test: `composer audit`, `npm audit`, `trivy fs` en `/review` y `/release` |
| P7 Auditoría de accesos y cambios a datos sensibles | ✅ | CA16: `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada` con `Auditoria::registrar()` en modo crítico, dentro de la transacción; el 403 lo registra `RegistroCentralDenegaciones` (spec 020) | test: `AuditoriaGaleriaTest` |
| P8 Correlación y logs sin datos sensibles | ✅ | Un solo log nuevo, `galeria.procesado_fallido` (`warning`), con `request_id` del `Context`, el tipo detectado y el tamaño, sin nombre de archivo ni descripción. Los eventos de auditoría no llevan la descripción | test: `SubidaFotoGaleriaTest::test_un_fallo_al_procesar_no_publica_nada_y_registra_el_aviso` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | La primera tarea aplica a `system.md` (1.12.0) y `system.html` los «Cambios a incorporar al sistema» de `design.md` (`--foto-ratio`, `--carrusel-foto-w`, `--thumb-w` y los componentes). Las pantallas usan solo tokens. WCAG 2.1 AA medido con `evaluate.mjs` y `design/criterios.json` | test: `GaleriaCssTest` (sin literales en las reglas nuevas de `publico.css` y `app.css`; tokens nuevos en `system.md`) · manual: `evaluate.mjs` con `design/medir.mjs` sobre la build, y `node docs/design/capturar.mjs --medir` (toca `layouts/app.blade.php`) |
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
| `app/Galeria/ProcesadorFoto.php` (nuevo) | `procesar(UploadedFile): FotoProcesada` (bytes WebP, ancho, alto): orientación EXIF, recorte 4:3, reducción a 1600 × 1200 como mucho, WebP con calidad 82, 72, 62 y 52 hasta pesar 500 kB o menos; lanza `FotoNoProcesable` si GD no la puede leer | Medio: memoria con fotos grandes (6000 × 6000 son unos 144 MB en GD, con `memory_limit` 256M) |
| `app/Galeria/Galeria.php` (nuevo) | Operaciones con transacción: `publicar(...)` (límite de 12 con `lockForUpdate`, posición al final, consentimiento, archivo y auditoría), `mover(foto, ±1)` (intercambia con la vecina) y `quitar(foto)` (borra la fila, compacta posiciones, borra el archivo después del *commit*) | Medio: archivo y fila tienen que quedar coherentes si algo falla |
| `app/Autorizacion/GestionGaleria.php` (nuevo) y `app/Providers/AppServiceProvider.php` | Gate `gestionar-galeria` (`rol === 'admin'`), registrado como los demás (ADR 0003). La 014 también toca `AppServiceProvider`: solo se añade una línea | Bajo |
| `app/Http/Controllers/FotosGaleriaController.php` (nuevo) | `index` (lista, formulario y, con `{foto}` en la ruta de quitar, el diálogo abierto), `store`, `subir`, `bajar`, `confirmarQuitar`, `destroy`; `reglasDeSubida()` y los mensajes de CA11 y CA12 | Medio |
| `app/Http/Controllers/PaginasPublicasController.php` | `inicio` pasa `fotos`; nuevos `galeria()` y `foto(GaleriaFoto)` (sirve el WebP) | Bajo |
| `routes/web.php` | Públicas: `GET /galeria` (`galeria.publica`) y `GET /galeria/fotos/{foto}` (`galeria.foto`, `whereUuid`). Grupo `admin`: `GET /admin/galeria`, `POST /admin/galeria`, `PATCH /admin/galeria/{foto}/subir`, `PATCH /admin/galeria/{foto}/bajar`, `GET /admin/galeria/{foto}/quitar`, `DELETE /admin/galeria/{foto}` (`admin.galeria.*`, `whereUuid`) | Bajo |
| `resources/views/publico/inicio.blade.php` | Sección «Nuestros trabajos» después del avance de servicios, solo `@if ($fotos->isNotEmpty())` (CA3) | Bajo: la 040 también añadirá su sección en ese hueco |
| `resources/views/publico/galeria.blade.php` (nuevo) y `resources/views/publico/_trabajo.blade.php` (nuevo) | `/galeria` con el mosaico y su estado vacío; la foto de trabajo compartida (numeral, «Foto n de N», descripción y `img` con `width`, `height` y `loading`) | Bajo |
| `resources/views/galeria/index.blade.php` (nuevo) | Gestión: tarjetas, formulario «Nueva foto», errores, aviso y diálogo de quitar, en `layouts.app` | Medio: estados del formulario |
| `resources/views/layouts/app.blade.php` | Entrada «Galería» en el menú del admin, después de «Servicios» | Medio: toca el menú del móvil, se mide con `capturar.mjs --medir` |
| `public/css/publico.css` | Carrusel, foto de trabajo, mosaico y galería vacía (de `c3/galeria.css`) | Bajo |
| `public/css/app.css` | Tarjeta de foto, pregunta del paciente, consentimiento, selector de archivo y aviso de error, dentro de `.formulario-accesible` (de `c3/gestion.css`) | Bajo |
| `public/js/publico.js` y `tests/js/publico.test.mjs` | Carrusel (de `c3/galeria.js`) en el mismo script, para seguir con un solo script propio por ruta; la función que elige la foto vecina se exporta para su test | Bajo |
| `docs/design/system.md`, `docs/design/system.html` | Versión 1.12.0 con los cambios de `design.md` | Bajo |
| `tests/Feature/Galeria/*Test.php`, `tests/Unit/Galeria/ProcesadorFotoTest.php` (nuevos), `tests/Feature/PaginasPublicasTest.php` | Ver Estrategia de pruebas | — |

## Contratos y datos
**Tabla `galeria_fotos`** (migración nueva, solo *expand*; la versión anterior la ignora):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `uuid` PK | también es el nombre del archivo en el disco `galeria` |
| `descripcion` | `varchar(160)` | texto plano; es el `alt` y el pie |
| `posicion` | `smallint unsigned` | 1…N, sin huecos; índice único |
| `con_paciente` | `boolean` | |
| `consentimiento_usuario_id` | `uuid` null, FK `usuarios.id` `nullOnDelete` | solo si `con_paciente` |
| `consentimiento_nombre` | `varchar(201)` null | nombre y apellido de quien declaró, copiados al publicar: la declaración se conserva aunque la cuenta cambie |
| `consentimiento_en` | `timestamp` null | |
| `ancho`, `alto` | `smallint unsigned` | medidas del WebP publicado, para `width` y `height` del `img` |
| `bytes` | `int unsigned` | ≤ 512 000 |
| `creado_en`, `actualizado_en` | `timestamp` | |

**Rutas:**

| Método y ruta | Nombre | Quién | Respuesta |
|---|---|---|---|
| `GET /galeria` | `galeria.publica` | todos (CA7: igual con sesión) | 200 con el mosaico o el estado vacío |
| `GET /galeria/fotos/{foto}` | `galeria.foto` | todos | 200 `image/webp`, `nosniff`, `Cache-Control: public, max-age=3600` · 404 si no existe o el parámetro no es un UUID |
| `GET /admin/galeria` | `admin.galeria.index` | admin | 200 |
| `POST /admin/galeria` | `admin.galeria.store` | admin | 302 a la lista con «Foto publicada.» · 302 atrás con errores (CA11, CA12), sin guardar nada |
| `PATCH /admin/galeria/{foto}/subir` y `/bajar` | `admin.galeria.subir`, `admin.galeria.bajar` | admin | 302 a la lista; en el extremo, sin cambios |
| `GET /admin/galeria/{foto}/quitar` | `admin.galeria.quitar` | admin | 200: la lista con el diálogo «¿Quitar esta foto?» abierto (funciona sin JavaScript) |
| `DELETE /admin/galeria/{foto}` | `admin.galeria.destroy` | admin | 302 a la lista con «Foto quitada.» |

Dentista, asistente o paciente en cualquier ruta `admin.galeria.*`: 403 (`role:admin`), registrado
por la spec 020. Sin sesión: 302 al inicio de sesión.

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
  - el resultado es WebP 4:3 de 1600 × 1200 como mucho;
  - pesa 500 kB o menos con una imagen de ruido de 6000 × 4500;
  - no contiene `Exif`, `GPS` ni `XMP`;
  - se endereza según la orientación;
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

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un dentista, asistente o paciente gestiona la galería con peticiones a mano | A01:2025 | rutas `admin.galeria.*` | `role:admin` en el grupo y `Gate::authorize('gestionar-galeria')` en cada acción; 403 registrado | `GestionGaleriaAccesoTest` (cada acción × rol → 403 y la galería sin cambios; sin sesión → inicio de sesión) |
| TM2 | Tampering: un ejecutable o un políglota PHP disfrazado de `.jpg` | A05:2025 · A08:2025 | subida y entrega | tipo por contenido (`mimes` con finfo) y decodificación con GD. Se guardan solo los píxeles recodificados en WebP, con el UUID como nombre, fuera de `public/`; se sirven como `image/webp` con `nosniff` | `SubidaFotoGaleriaTest::test_un_archivo_que_no_es_imagen_aunque_acabe_en_jpg_se_rechaza` · `ProcesadorFotoTest::test_el_resultado_es_webp_recodificado` · `PaginaGaleriaTest::test_la_foto_se_sirve_como_webp_con_nosniff` |
| TM3 | DoS: archivos enormes, bombas de descompresión o llenar el disco | A06:2025 | subida | 6 MB en nginx, 5 MB en la validación, como mucho 6000 px por lado antes de decodificar (`dimensions` lee la cabecera), 12 fotos con `lockForUpdate`, sin conservar originales | `SubidaFotoGaleriaTest` (más de 5 MB, más de 6000 px, la número 13) |
| TM4 | Information disclosure: ubicación u otros datos de la cámara en la foto publicada | A02:2025 | procesado | GD no copia metadatos al recodificar; se aplica la orientación y no se guarda el original | `ProcesadorFotoTest::test_no_quedan_datos_de_la_camara` · `PaginaGaleriaTest::test_la_foto_descargada_no_tiene_exif` (CA14) |
| TM5 | XSS almacenado por la descripción | A05:2025 | vistas públicas y gestión | salida escapada de Blade en el pie y en `alt`; el JS escribe con `textContent` | `PaginaGaleriaTest::test_la_descripcion_se_muestra_como_texto` (CA15) · `CarruselInicioTest` (mismo caso en `/`) |
| TM6 | Path traversal o enumeración en `/galeria/fotos/{foto}` | A01:2025 | entrega | `whereUuid` y `GaleriaFoto` resuelto por Eloquent; la ruta del archivo sale del `id` de la fila | `PaginaGaleriaTest::test_una_direccion_que_no_es_una_foto_responde_404` (`../`, texto, UUID inexistente) |
| TM7 | Repudiation: nadie responde de una foto con paciente publicada | A09:2025 | publicación | declaración con cuenta, nombre copiado y fecha (CA9); evento `galeria_foto_publicada` crítico con `con_paciente` | `GestionGaleriaTest::test_la_declaracion_muestra_quien_y_cuando` · `AuditoriaGaleriaTest` |
| TM8 | Information disclosure: una foto quitada (consentimiento retirado) sigue accesible | A01:2025 | baja y entrega | se borran la fila y el archivo; la dirección da 404. Sin extensión, Cloudflare no la guarda en caché, y el navegador la conserva como mucho una hora (Riesgos) | `GestionGaleriaTest::test_la_foto_quitada_deja_de_abrirse` (CA10) |
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
| CA10: cambiar el orden o quitar (tras confirmar) se refleja en la siguiente visita; la quitada no se abre | `subir`, `bajar`, `confirmarQuitar`, `destroy`, `Galeria::mover`, `Galeria::quitar` | `GestionGaleriaTest::test_subir_y_bajar_cambian_el_orden`, `::test_quitar_pide_confirmacion`, `::test_la_foto_quitada_deja_de_abrirse`. verificación manual: `medir.mjs` (K8) y comparación con `c3/gestion-quitar.html` |
| CA11: sin descripción, sin indicar paciente o sin consentimiento → su mensaje y nada publicado | `reglasDeSubida()`, `galeria/index.blade.php` | `SubidaFotoGaleriaTest::test_cada_falta_tiene_su_mensaje_y_no_publica` (dataProvider con los tres literales; ni fila ni archivo). verificación manual: `medir.mjs` (K7, K7b) |
| CA12 (abuso): no imagen aunque sea `.jpg`, otro formato, > 5 MB o la número 13 → motivo y nada guardado | `reglasDeSubida()`, `Galeria::publicar` | `SubidaFotoGaleriaTest::test_un_archivo_que_no_es_imagen_aunque_acabe_en_jpg_se_rechaza`, `::test_otro_formato_se_rechaza` (GIF), `::test_mas_de_5_mb_se_rechaza`, `::test_mas_de_6000_px_se_rechaza`, `::test_con_12_fotos_no_se_publica_la_siguiente` |
| CA13 (abuso): dentista, asistente, paciente o visitante → negado y sin cambios | `routes/web.php` (grupo `admin`), `GestionGaleria`, `FotosGaleriaController` | `GestionGaleriaAccesoTest::test_otros_roles_reciben_403_en_cada_accion` (dataProvider rol × acción), `::test_sin_sesion_va_al_inicio_de_sesion` |
| CA14 (abuso): la descarga no conserva los datos de la cámara | `ProcesadorFoto` | `ProcesadorFotoTest::test_no_quedan_datos_de_la_camara`, `PaginaGaleriaTest::test_la_foto_descargada_no_tiene_exif` |
| CA15 (abuso): la descripción con etiquetas se muestra como texto | vistas con la salida escapada de Blade, `publico.js` con `textContent` | `PaginaGaleriaTest::test_la_descripcion_se_muestra_como_texto`, `CarruselInicioTest::test_la_descripcion_se_muestra_como_texto` |
| CA16: publicar, mover y quitar quedan registrados (quién, acción, foto, si hay paciente, cuándo, resultado, sin descripción ni imagen); el 403 con sesión, como acceso denegado | `Galeria` (`Auditoria::registrar`, crítico), `RegistroCentralDenegaciones` (existente) | `AuditoriaGaleriaTest::test_publicar_mover_y_quitar_quedan_registrados`, `::test_el_evento_no_lleva_la_descripcion`, `::test_si_la_auditoria_falla_no_se_publica_nada`, `::test_el_acceso_denegado_queda_registrado` |
| RNF: WCAG 2.1 AA; el carrusel no se mueve solo y anuncia la foto | vistas, `publico.js` | `CarruselInicioTest::test_la_region_de_estado_esta_montada_y_vacia`, `publico.test.mjs`. verificación manual: puertas de `medir.mjs` y recorrido con teclado contra `c3` |
| RNF: `/galeria` indexable, Lighthouse SEO ≥ 90, JavaScript ≤ 100 kB, contenido en el HTML del servidor | `galeria.blade.php` | `PaginaGaleriaTest::test_metadatos_de_galeria`, `::test_solo_carga_el_script_publico_con_defer`. verificación manual: K10 de `medir.mjs` y Lighthouse en `/review` |
| RNF: cada foto ≤ 500 kB; las de fuera de pantalla con carga diferida | `ProcesadorFoto`, `_trabajo.blade.php` | `ProcesadorFotoTest::test_pesa_como_mucho_500_kb`, `CarruselInicioTest::test_solo_la_primera_foto_carga_sin_esperar` y en `/galeria` las tres primeras |
| TM1 | ver CA13 | `GestionGaleriaAccesoTest` |
| TM2 | `ProcesadorFoto`, `PaginasPublicasController::foto` | ver Modelo de amenazas |
| TM3 | nginx, `reglasDeSubida()`, `Galeria::publicar` | ver CA12 |
| TM4 | `ProcesadorFoto` | ver CA14 |
| TM5 | vistas, `publico.js` | ver CA15 |
| TM6 | `routes/web.php` (`whereUuid`), `PaginasPublicasController::foto` | `PaginaGaleriaTest::test_una_direccion_que_no_es_una_foto_responde_404` |
| TM7 | `Galeria::publicar` | ver CA9 y CA16 |
| TM8 | `Galeria::quitar` | ver CA10 |
| TM9 | `galeria/index.blade.php` | `GestionGaleriaTest::test_los_formularios_llevan_token_csrf` |

## Observabilidad
- **Logs nuevos:** `galeria.procesado_fallido` → `warning` → `request_id` (por el `Context`),
  `tipo_detectado`, `bytes`. Sin nombre de archivo, descripción ni actor; el actor ya está en el
  `Context`.
- **Eventos de auditoría** (`Auditoria::registrar`, crítico, dentro de la transacción de `Galeria`):
  - `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada`;
  - resultado `exito`, `recurso_tipo` `galeria_foto` y `recurso_id` el UUID;
  - detalle: `con_paciente` (bool) y `posicion` (int), más `direccion` (`arriba` o `abajo`) al mover;
  - nunca la descripción ni la imagen.

  El 403 lo registra `RegistroCentralDenegaciones` como `acceso_denegado`, con `accion_solicitada` =
  nombre de la ruta y `recurso_tipo` `galeria`. Tests en `AuditoriaGaleriaTest` (Trazabilidad CA16).
  `docs/observability.md` gana sus filas (T090).
- **Métricas y alertas:** ninguna nueva. El vigilante (`dentissa:vigilar`) ya avisa de los 5xx y de
  los fallos de auditoría.
- **Cómo se verifica tras el deploy:**
  1. Publicar una foto de prueba sin paciente y comprobar que se ve en `/` y en `/galeria`.
  2. En `/admin/auditoria`, ver `galeria_foto_publicada` con el `request_id` de la petición.
  3. Quitar la foto y comprobar que su dirección responde 404.
  4. Revisar `dentissa-error.log` (sin 413) y `laravel.log` (sin `galeria.procesado_fallido`).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/galeria` | sí | «Nuestros trabajos · Dentissa» | «Fotos de tratamientos hechos en la consulta Dentissa en Lázaro Cárdenas.» | `https://dentissa.app/galeria` (`rtrim(config('seo.site_url'))`, como en la 038) | no: el sitemap no existe todavía (objetivo 7, que tendrá que incluir `/galeria`) | — | `PaginaGaleriaTest::test_metadatos_de_galeria`, `PaginasPublicasTest` (únicos) |
| `/` | sí (sin cambios) | sin cambios | sin cambios | sin cambios | — | — | `PaginasPublicasTest` (sigue pasando) |
| `/galeria/fotos/{foto}` | no es HTML: imagen pública | — | — | — | no | — | `PaginaGaleriaTest::test_la_foto_se_sirve_como_webp_con_nosniff` |

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
- **Orden de despliegue:** lo hace `deploy/desplegar.sh vX.Y.Z` en un solo paso:
  1. construye las imágenes, ya con GD con JPEG y WebP y nginx a 6 MB;
  2. ejecuta la migración *expand* `create_galeria_fotos_table`;
  3. levanta la aplicación.

  El directorio `storage/app/galeria` se crea con la primera foto, dentro del volumen `storage`.
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
- **D2 · Qué se guarda (→ ADR 0009).** Solo el WebP recodificado: recortado al centro a 4:3, como
  mucho 1600 × 1200, de 500 kB o menos y sin metadatos. Nunca el original.
  - Recortar al publicar sigue a `design.md` (`--foto-ratio`) y evita saltos (CLS).
  - Recodificar quita los datos de la cámara (CA14) y deja inservible cualquier carga útil escondida
    (TM2).
- **D3 · Dónde y cómo se sirve (→ ADR 0009).** Disco `galeria` en `storage/app/galeria` (volumen
  `storage`), servido por `GET /galeria/fotos/{foto}`.
  - Opciones: disco `public` con `storage:link` y nginx; servir desde la aplicación.
  - Decisión: desde la aplicación, porque `web` no monta el volumen y son como mucho 12 fotos.
  - Sin extensión en la URL: Cloudflare no la guarda en caché, y CA10 se cumple al borrar.
- **D4 · Copias (decisión del usuario, 2026-11-07).** Las fotos no entran en `respaldar.sh` por ahora.
  Riesgo en `docs/deployment.md` y deuda en el roadmap (T090, T091).
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
- **D9 · Límite de 6000 px por lado.** No lo pide la spec, pero cae dentro de CA12 (archivo enorme
  disfrazado). Una foto de 6000 × 6000 son unos 144 MB en GD; con `memory_limit` 256M cabe
  decodificar y reducir. Los móviles guardan por defecto fotos de 12 MP (4000 × 3000).
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
- `docs/security.md`: las fotos con paciente como datos de salud publicados con consentimiento, y la
  subida de archivos.
- `docs/observability.md`: los eventos de la galería.
- `docs/seo.md`: la fila de `/galeria`.
- `AGENTS.md`, mapa del repositorio (ruta protegida, con aprobación al implementar): `app/Galeria/`.
- ADR 0009 `accepted` (plan aprobado por el usuario el 2026-11-07).

## Riesgos y mitigaciones
- **Paquetes nuevos en la imagen (`libjpeg`, `libwebp`) con vulnerabilidades.** `trivy` en `/review`
  y `/release`; EX2 ya cubre las que no tienen corrección en la imagen base.
- **Memoria al decodificar fotos grandes.** Límite de 6000 px, verificado antes de decodificar.
  Prueba con una imagen de 6000 × 4500 en `ProcesadorFotoTest`, y vigilancia de la memoria de `app`
  tras el deploy.
- **Archivo y fila incoherentes si algo falla.**
  - Publicar: el archivo se escribe dentro de la transacción y se borra si esta falla.
  - Quitar: el archivo se borra después del *commit*. Si ese borrado fallara, queda un archivo
    huérfano que nadie sirve, porque la ruta exige la fila; se registra con un log `warning`.
- **Sin copia de las fotos (D4).** Si se pierde el volumen, el admin vuelve a subirlas. Las
  declaraciones de consentimiento están en la base, que sí se copia. Va al roadmap.
- **Caché del navegador.** Una foto quitada puede seguir en el navegador de un visitante hasta una
  hora. La dirección ya responde 404 y no hay caché intermedia.
- **K11 del inicio (CLS de la página actual).** Fuera de la 039 (D14); el `/fix` de la 038 lo
  corrige.
- **Solape con la 040 y la 014.** La 040 añade su sección en el mismo hueco de `inicio.blade.php`, y
  la 014 toca `AppServiceProvider`. Se implementa la 039 primero (D12) y en ese archivo solo se
  añade una línea.
- **EX4 vence el 2026-11-11.** El `/release` de la 039 necesitará antes el `/chore` de Symfony 7.4.23,
  o `composer audit` fallará.
