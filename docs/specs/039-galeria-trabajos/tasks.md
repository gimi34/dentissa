---
spec: 039-galeria-trabajos
plan: plan.md
status: approved
---

# Tareas · 039 Galería de trabajos

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Diseño:** el candidato aceptado es `design/candidatos/c3/` (`design.md`, aprobado). Cada pantalla
  se mide con `node docs/specs/039-galeria-trabajos/design/medir.mjs <carpeta> --evaluate <evaluate.mjs> --out .ai/inspect/039-build`
  sobre las páginas renderizadas por la app en local:
  - las públicas, con `curl` a `http://localhost:8000`;
  - las del admin, con la sesión de la cuenta ficticia «Admin Sistema», como en `/design-spec`;
  - en los dos casos, guardadas en `.ai/inspect/039-build/` con las rutas de `public/` relativas.

  K11 del inicio queda como en la página actual (0,212; plan D14).
- **Contenedor de desarrollo:** desde T002 hay que reconstruirlo (`docker compose build app` y
  `docker compose up -d app`) para que GD lea y escriba JPEG y WebP. Los tests con imágenes JPG dependen
  de T002.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, diseño y plan aprobados (2026-11-07). Ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Las tareas de test T010–T018 van antes que las de implementación T020–T028, y cada una de estas depende de sus tests. CA3, que ya se cumple, se comprueba rompiendo el `@if` en T028, con su nota | test: T010–T018 |
| P3 Autorización en servidor | ✅ | T022 (Gate) y T023 (grupo `admin` y `Gate::authorize` en cada acción); T013 prueba cada acción × rol | test: T013 |
| P4 Entrada validada y SQL con bindings | ✅ | Las reglas de subida van en `FotosGaleriaController::reglasDeSubida()` (T023) y `{foto}` con `whereUuid` (T023, T024), sin SQL a mano. T011 prueba cada regla | test: T011, T014 (TM6) · lint: semgrep en T030 |
| P5 Nada sensible en el repo | ✅ | Las imágenes de prueba se generan en el trait de T010; no se versiona ninguna foto | lint: gitleaks en T030 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | T002 añade paquetes del sistema (no de Composer ni npm); T030 ejecuta `trivy` sobre las imágenes nuevas | lint: `composer audit`, `npm audit`, `trivy fs` en T030 |
| P7 Auditoría de datos sensibles | ✅ | T021 emite los tres eventos en modo crítico; T016 los prueba junto con el 403 registrado | test: T016 |
| P8 Correlación y logs limpios | ✅ | Un solo `Log::warning` (`galeria.procesado_fallido`) en T021, sin nombre de archivo ni descripción; T011 lo prueba | test: T011 · manual: diff de `Log::` en T030 |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 (primera tarea) aplica a `system.md` los cambios de `design.md`; T025, T027 y T028 dependen de ella. T017 prueba que no hay literales | test: T017 · manual: `medir.mjs` en T025, T027, T028 y T030; `capturar.mjs --medir` en T026 |
| P10 Rutas indexables descubribles | ✅ | `/galeria` con título, descripción, canónica y un solo script (T027); T014 lo prueba y T030 pasa Lighthouse | test: T014 · manual: Lighthouse SEO ≥ 90 en T030 |

## Preparación
- [ ] T001 Aplicar los «Cambios a incorporar al sistema» de `design.md` a `system.md` (versión 1.12.0, tokens `--foto-ratio`, `--carrusel-foto-w` y `--thumb-w` con su modo, componentes de la superficie pública y de `.formulario-accesible`, entrada «Galería» del menú y su fila en Decisiones) y a `system.html` — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los tres tokens y los nueve componentes de `design.md` → Componentes están documentados con sus valores y estados, `version: 1.12.0`, y `criterios.json → tokens.colors` sigue igual (no hay colores nuevos)
- [ ] T002 Compilar GD con JPEG y WebP en las dos imágenes (`libjpeg62-turbo-dev`, `libwebp-dev`, `docker-php-ext-configure gd --with-jpeg --with-webp`; `Dockerfile` es ruta protegida, aprobada en el plan D1) y reconstruir el contenedor de desarrollo — `Dockerfile`, `docker/prod/Dockerfile` — hecho cuando: en `dentissa_app`, `gd_info()` da `JPEG Support` y `WebP Support` verdaderos, y `docker build -f docker/prod/Dockerfile --target app .` termina y su `php -r 'print_r(gd_info());'` da lo mismo
- [ ] T003 [P] Aceptar cuerpos de hasta 6 MB en nginx (`client_max_body_size 6m;` en el `server`) — `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` — hecho cuando: tras `docker compose restart nginx`, un `POST` de 5,5 MB a `http://localhost:8000/admin/galeria` sin sesión responde 302 o 419, no 413 (`curl -s -o /dev/null -w '%{http_code}' --data-binary @archivo`)
- [ ] T004 [P] Crear la tabla `galeria_fotos` (columnas de Contratos y datos, `posicion` única, FK `consentimiento_usuario_id` con `nullOnDelete`), el modelo `GaleriaFoto` (UUID, `creado_en` y `actualizado_en`, orden por `posicion`) y el disco `galeria` en `storage/app/galeria` sin URL pública — `database/migrations/2026_11_07_000000_create_galeria_fotos_table.php`, `app/Models/GaleriaFoto.php`, `config/filesystems.php` — hecho cuando: `php artisan migrate` pasa en `dentissa` y en `dentissa_test`, y `migrate:rollback --step=1` la quita y se vuelve a aplicar sin errores

## Tests (antes de implementar)
- [ ] T010 Escribir los tests unitarios del procesado, con un trait de imágenes de prueba (JPG, PNG, WebP y GIF con GD; JPEG con un segmento APP1 de EXIF, GPS y orientación sintéticos; ruido de 6000 × 4500; archivo de texto con extensión `.jpg`) y la firma vacía de `ProcesadorFoto::procesar()` que lanza «sin implementar» — `tests/Feature/Concerns/GaleriaDePrueba.php`, `tests/Unit/Galeria/ProcesadorFotoTest.php`, `app/Galeria/ProcesadorFoto.php` — hecho cuando: los casos (WebP 4:3 de 1600 × 1200 como mucho, ≤ 512 000 bytes con el ruido, sin `Exif`, `GPS` ni `XMP`, enderezada según la orientación, excepción si no decodifica) fallan por «sin implementar» — cubre: CA8, CA14 — depende: T002
- [ ] T011 Escribir `SubidaFotoGaleriaTest` — `tests/Feature/Galeria/SubidaFotoGaleriaTest.php` — hecho cuando: falla por la ruta inexistente. Prueba: publicar jpg, png y webp al final, con el `alt`, y el archivo en el disco falso; los tres mensajes de CA11 (dataProvider) y el de sin archivo, sin fila ni archivo; CA12: texto con `.jpg`, GIF, más de 5 MB, más de 6000 px y la foto número 13, cada uno con su mensaje y sin guardar nada; más de 160 caracteres; un fallo de GD: no publica nada y registra `galeria.procesado_fallido` sin el nombre del archivo. — cubre: CA8, CA11, CA12 — depende: T010
- [ ] T012 Escribir `GestionGaleriaTest` — `tests/Feature/Galeria/GestionGaleriaTest.php` — hecho cuando: falla por la ruta inexistente. Prueba: con paciente y consentimiento, se publica con cuenta, nombre copiado y fecha; la tarjeta muestra «Consentimiento declarado por {nombre} el {fecha}»; «Arriba» y «Abajo» cambian el orden visible en `/galeria`, y en los extremos no hacen nada; `GET …/quitar` muestra «¿Quitar esta foto?» con «Quitar» y «Cancelar» sin borrar nada; `DELETE` la quita, compacta las posiciones y su dirección responde 404; cada formulario lleva el token CSRF. — cubre: CA9, CA10 — depende: T010
- [ ] T013 Escribir `GestionGaleriaAccesoTest` — `tests/Feature/Galeria/GestionGaleriaAccesoTest.php` — hecho cuando: falla por la ruta o la entrada de menú inexistentes. Prueba: dentista, asistente y paciente reciben 403 en las seis acciones de `admin.galeria.*` (dataProvider rol × acción) y la galería no cambia; sin sesión, cada acción lleva al inicio de sesión; el admin ve la entrada «Galería» del menú y los demás roles no. — cubre: CA13 — depende: T010
- [ ] T014 Escribir `PaginaGaleriaTest` y añadir `/galeria` a los metadatos de `PaginasPublicasTest` — `tests/Feature/Galeria/PaginaGaleriaTest.php`, `tests/Feature/PaginasPublicasTest.php` — hecho cuando: los casos nuevos fallan por la ruta inexistente. Prueba: todas las fotos en orden con su descripción y «Volver al inicio»; vacía, «Aún no hay fotos de nuestros trabajos.»; título, descripción y canónica de CA6, sin `noindex`, y únicos entre rutas; con sesión de los cuatro roles, igual; la foto descargada es `image/webp` con `nosniff`, sin EXIF; una descripción con `<script>` sale escapada; `../`, texto y un UUID inexistente dan 404; un solo script propio con `defer`; las tres primeras `img` sin `loading="lazy"` y el resto con él. — cubre: CA2, CA6, CA7, CA14, CA15 — depende: T010
- [ ] T015 Escribir `CarruselInicioTest` — `tests/Feature/Galeria/CarruselInicioTest.php` — hecho cuando: los casos con fotos fallan por la sección inexistente y el de sin fotos pasa. Prueba: la sección va después de «Ver todos los servicios» y antes de la agenda, con las fotos en orden, sus descripciones y «Foto n de N»; «Anterior» y «Siguiente» son enlaces `#trabajo-n`, y está «Ver toda la galería» a `/galeria`; las seis `img` están en el HTML; solo la primera sin `loading="lazy"`; la región `role="status"` está montada y vacía; la descripción con `<script>` sale escapada; sin fotos, no hay sección ni enlace (CA3, caracterización). — cubre: CA1, CA3, CA4, CA15 — depende: T010
- [ ] T016 Escribir `AuditoriaGaleriaTest` — `tests/Feature/Galeria/AuditoriaGaleriaTest.php` — hecho cuando: falla por la ruta inexistente. Prueba: `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada` con actor, `recurso_tipo` `galeria_foto`, `recurso_id`, `con_paciente`, `posicion` y resultado `exito`; ningún evento lleva la descripción; si el almacén de auditoría falla, no queda ni fila ni archivo; el 403 de un dentista en `admin.galeria.store` queda como `acceso_denegado` con `accion_solicitada` `admin.galeria.store`. — cubre: CA16 — depende: T010
- [ ] T017 [P] Escribir `GaleriaCssTest` (con el trait `ReglasCss`) — `tests/Feature/Galeria/GaleriaCssTest.php` — hecho cuando: falla porque faltan las reglas. Prueba: las reglas `.pub-carrusel*`, `.pub-trabajo*`, `.pub-mosaico` y `.pub-galeria__vacia` de `publico.css`, y `.foto-tarjeta*`, `.campo-paciente`, `.opcion`, `.consentimiento`, `.campo-archivo` y `.aviso-error` de `app.css`, existen y solo usan tokens; las transiciones del carrusel solo aparecen bajo `prefers-reduced-motion: no-preference`; los tres tokens nuevos están en `system.md`. — cubre: CA5 — depende: T001
- [ ] T018 [P] Añadir los tests del carrusel a `publico.test.mjs` — `tests/js/publico.test.mjs` — hecho cuando: `node --test tests/js/publico.test.mjs` falla en los casos nuevos porque `publico.js` no exporta la función. Prueba: la foto vecina (de la primera a la última y al revés); el texto de estado sin el numeral `aria-hidden`; ningún temporizador de avance automático. — cubre: CA1

## Implementación
- [ ] T020 Implementar el procesado: decodificar con GD, aplicar la orientación EXIF, recortar al centro a 4:3, reducir a 1600 × 1200 como mucho y codificar en WebP con calidad 82, 72, 62 y 52 hasta pesar 512 000 bytes o menos; `FotoProcesada` (bytes, ancho, alto); `FotoNoProcesable` si no decodifica — `app/Galeria/ProcesadorFoto.php`, `app/Galeria/FotoProcesada.php`, `app/Galeria/FotoNoProcesable.php` — hecho cuando: T010 pasa — cubre: CA8, CA14 — depende: T010, T002
- [ ] T021 Implementar las operaciones de `Galeria`: `publicar(UploadedFile, descripcion, conPaciente, Usuario)`: transacción, límite de 12 con `lockForUpdate`, posición al final, consentimiento con nombre copiado y fecha, archivo en el disco `galeria` (que se borra si la transacción falla) y `galeria_foto_publicada` crítico; `mover(GaleriaFoto, ±1)`: intercambia con la vecina y registra `galeria_foto_movida`; `quitar(GaleriaFoto)`: borra la fila, compacta, registra `galeria_foto_quitada` y borra el archivo después del *commit*, con un log `warning` si falla; `galeria.procesado_fallido` (`warning`) ante `FotoNoProcesable`. — `app/Galeria/Galeria.php` — hecho cuando: los casos de T016 que no dependen de rutas pasan y el resto lo hace con T023 — cubre: CA8, CA9, CA10, CA12, CA16 — depende: T020, T004, T016
- [ ] T022 [P] Registrar el Gate `gestionar-galeria` con `GestionGaleria::autorizar(Usuario)` (`rol === 'admin'`), una sola línea en `AppServiceProvider` (ADR 0003) — `app/Autorizacion/GestionGaleria.php`, `app/Providers/AppServiceProvider.php` — hecho cuando: `Gate::forUser($admin)->allows('gestionar-galeria')` es verdadero y para los demás roles falso (en T013) — cubre: CA13 — depende: T013
- [ ] T023 Implementar `FotosGaleriaController` y sus rutas: acciones: `index` (con `{foto}` opcional para el diálogo), `store`, `subir`, `bajar`, `confirmarQuitar` y `destroy`, cada una con `Gate::authorize('gestionar-galeria')`; `reglasDeSubida()` y los mensajes de Contratos y datos; rutas `admin.galeria.*` en el grupo `admin`, con `whereUuid`; todo desde `Galeria`, con los avisos «Foto publicada.» y «Foto quitada.». — `app/Http/Controllers/FotosGaleriaController.php`, `routes/web.php` — hecho cuando: T011, T013 (salvo la entrada de menú) y T016 pasan, con T025 para las vistas — cubre: CA8, CA10, CA11, CA12, CA13, CA16 — depende: T021, T022, T011, T013
- [ ] T024 Añadir a `PaginasPublicasController`: `fotos` para `inicio` (ordenadas); `galeria()`; `foto(GaleriaFoto)`: sirve el WebP con `Content-Type: image/webp`, `X-Content-Type-Options: nosniff` y `Cache-Control: public, max-age=3600`. Añadir las rutas públicas `galeria.publica` y `galeria.foto` (`whereUuid`), fuera de los grupos por rol — `app/Http/Controllers/PaginasPublicasController.php`, `routes/web.php` — hecho cuando: los casos de entrega y 404 de T014 pasan — cubre: CA2, CA7, CA10, CA14 — depende: T014, T004, T023
- [ ] T025 Construir la pantalla de gestión como `c3/gestion*.html`: tarjetas con miniatura, posición, declaración y «Arriba», «Abajo» y «Quitar» (enlace a `…/quitar`); formulario «Nueva foto»: selector de archivo, descripción, `fieldset` del paciente con radios de 44 px y consentimiento visible solo con «Sí»; errores junto a cada campo con `aria-invalid` y `aria-describedby`, y el aviso `role="alert"`; diálogo de quitar con `role="dialog"`, `aria-modal`, el foco en «Cancelar» y el formulario `DELETE`; estilos dentro de `.formulario-accesible`. — `resources/views/galeria/index.blade.php`, `public/css/app.css` — hecho cuando: T012 y los casos de vista de T011 pasan y `medir.mjs` pasa las cuatro pantallas de gestión · verificación manual: comparación con `design/candidatos/c3/gestion.html`, `gestion-error.html`, `gestion-consentimiento.html` y `gestion-quitar.html` a 320, 390 y 1440 px, y recorrido con teclado del formulario y del diálogo — cubre: CA9, CA10, CA11 — depende: T001, T023, T012
- [ ] T026 Añadir la entrada «Galería» al menú del admin, después de «Servicios», con `aria-current` en sus rutas — `resources/views/layouts/app.blade.php` — hecho cuando: el caso de menú de T013 pasa y `node docs/design/capturar.mjs --medir` sigue en verde (AGENTS.md) — cubre: CA13 — depende: T001, T013, T023
- [ ] T027 Construir `/galeria` como `c3/galeria.html` y `c3/galeria-vacia.html`: layout público, estructura de `/servicios`, título, descripción y canónica de CA6; mosaico con la 01 a doble ancho y alto desde 900 px, y estado vacío; la foto de trabajo compartida (numeral `aria-hidden`, «Foto n de N», descripción, `img` con `width`, `height`, `alt` y `loading`). Estilos `.pub-trabajo`, `.pub-mosaico`, `.pub-galeria__vacia` y `.pub-solo-lector` en `publico.css` — `resources/views/publico/galeria.blade.php`, `resources/views/publico/_trabajo.blade.php`, `public/css/publico.css` — hecho cuando: T014 pasa y `medir.mjs` pasa `galeria` y `galeria-vacia` · verificación manual: comparación con `design/candidatos/c3/galeria.html` y `galeria-vacia.html` a 320, 390, 900 y 1440 px — cubre: CA2, CA5, CA6, CA15 — depende: T001, T024, T014
- [ ] T028 Construir la sección «Nuestros trabajos» como `c3/inicio.html`: después del avance de servicios, solo `@if ($fotos->isNotEmpty())`; ventana tabulable, lista `#trabajo-n`, controles «Anterior» y «Siguiente» y región de estado; el carrusel en `publico.js` (exportando la función de la vecina; estado con `textContent` sin el numeral; sin temporizadores); los estilos `.pub-carrusel*` en `publico.css`. — `resources/views/publico/inicio.blade.php`, `public/js/publico.js`, `public/css/publico.css` — hecho cuando: T015, T017 y T018 pasan; CA3 comprobado rompiendo el `@if`; `medir.mjs` pasa `inicio` salvo K11 (como la página actual) · verificación manual: comparación con `design/candidatos/c3/inicio.html` a 320, 390, 900 y 1440 px, y «Siguiente» y «Anterior» con teclado, con y sin movimiento reducido — cubre: CA1, CA3, CA4, CA5, CA15 — depende: T001, T024, T027, T015, T017, T018
- [ ] T030 Hacer la verificación final de la implementación: suite completa (línea base sin fallos nuevos), Pint sobre los archivos tocados y `node --test tests/js/publico.test.mjs`; gitleaks, semgrep, `composer audit`, `npm audit` y `trivy fs` (con las imágenes de T002); `medir.mjs` con las siete pantallas renderizadas por la app; `capturar.mjs --medir`; Lighthouse SEO ≥ 90 en `/galeria` con la build local. — `.ai/inspect/039-build/ (generado por design/medir.mjs)` — hecho cuando: todo en verde salvo K11 del inicio (plan D14), con los resultados en la nota de la tarea — depende: T025, T026, T027, T028
- [ ] T031 [P] Actualizar `docs/seo.md` (fila de `/galeria`, «Se indexa» con `/galeria`, la lista pendiente del sitemap del objetivo 7 con `/galeria`) y `docs/roadmap.md` (deuda: copias de las fotos de la galería, plan D4; `/fix` de la 038 para el CLS del inicio, plan D14) — `docs/seo.md`, `docs/roadmap.md` — hecho cuando: las dos entradas existen y `aidd.py validate` no da errores — depende: T027

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` (Módulos `app/Galeria/`, Datos `galeria_fotos` y disco `galeria`, Backend con las rutas y el Gate, Frontend con las vistas y el carrusel en `publico.js`, Despliegue con GD ampliado, nginx a 6 MB y las fotos en el volumen `storage`) y el mapa de `AGENTS.md` con `app/Galeria/` (ruta protegida: pedir aprobación) — `docs/architecture.md`, `AGENTS.md` — hecho cuando: las secciones citadas en «Impacto en arquitectura» del plan reflejan lo implementado
- [ ] T091 Actualizar `docs/deployment.md`, `docs/observability.md` y `docs/security.md`: deployment: fotos en el volumen `storage` fuera de las copias, como riesgo `RD` nuevo con su corrección pendiente; `client_max_body_size`; GD con JPEG y WebP; observability: filas de `galeria_foto_publicada`, `galeria_foto_movida` y `galeria_foto_quitada`, y el log `galeria.procesado_fallido`; security: fotos con paciente como datos de salud publicados con consentimiento, y la subida de archivos con sus controles. — `docs/deployment.md`, `docs/observability.md`, `docs/security.md` — hecho cuando: las tres secciones existen y el riesgo nuevo tiene su corrección con estado `pendiente`
- [ ] T092 Marcar spec como `implemented` — hecho cuando: CA1–CA16 marcados con sus tests en verde, `docs/specs/README.md` actualizado y `aidd.py validate` sin errores

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097, con la cuenta del administrador y una foto de prueba sin pacientes: publicarla y verla en `/` y en `/galeria`; en `/admin/auditoria`, `galeria_foto_publicada` con su `request_id`; quitarla y su dirección responde 404; sin 413 en `dentissa-error.log` ni `galeria.procesado_fallido` en `laravel.log` — resultado: —
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z`, `verificar.sh` y la comprobación de T095. Vigilar los 5xx en `/galeria`, `/galeria/fotos/*` y `POST /admin/galeria`, los 413, `auditoria.fallo` y la memoria de `app`. Rollback: `deploy/volver.sh <tag anterior>`, sin la copia
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T015, T018 | T028 |
| CA2 | T014 | T024, T027 |
| CA3 | T015 | T028 |
| CA4 | T015 | T028 |
| CA5 | T017 | T027, T028 |
| CA6 | T014 | T027 |
| CA7 | T014 | T024 |
| CA8 | T010, T011 | T020, T021, T023 |
| CA9 | T012 | T021, T025 |
| CA10 | T012 | T021, T023, T024, T025 |
| CA11 | T011 | T023, T025 |
| CA12 | T011 | T021, T023 |
| CA13 | T013 | T022, T023, T026 |
| CA14 | T010, T014 | T020, T024 |
| CA15 | T014, T015 | T027, T028 |
| CA16 | T016 | T021, T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T022, T023 | T013 |
| TM2 | T020, T024 | T010, T011, T014 |
| TM3 | T003, T021, T023 | T011 |
| TM4 | T020 | T010, T014 |
| TM5 | T027, T028 | T014, T015 |
| TM6 | T024 | T014 |
| TM7 | T021 | T012, T016 |
| TM8 | T021, T024 | T012 |
| TM9 | T025 | T012 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Dockerfile y docker/prod/Dockerfile | T002 |
| docker/nginx/dentissa.conf y dentissa.prod.conf | T003 |
| config/filesystems.php | T004 |
| database/migrations/2026_11_07_000000_create_galeria_fotos_table.php | T004 |
| app/Models/GaleriaFoto.php | T004 |
| app/Galeria/ProcesadorFoto.php | T010, T020 |
| app/Galeria/Galeria.php | T021 |
| app/Autorizacion/GestionGaleria.php y app/Providers/AppServiceProvider.php | T022 |
| app/Http/Controllers/FotosGaleriaController.php | T023 |
| app/Http/Controllers/PaginasPublicasController.php | T024 |
| routes/web.php | T023, T024 |
| resources/views/publico/inicio.blade.php | T028 |
| resources/views/publico/galeria.blade.php y _trabajo.blade.php | T027 |
| resources/views/galeria/index.blade.php | T025 |
| resources/views/layouts/app.blade.php | T026 |
| public/css/publico.css | T027, T028 |
| public/css/app.css | T025 |
| public/js/publico.js y tests/js/publico.test.mjs | T018, T028 |
| docs/design/system.md y system.html | T001 |
| tests/Feature/Galeria/*Test.php, tests/Unit/Galeria/ProcesadorFotoTest.php, tests/Feature/PaginasPublicasTest.php | T010–T017 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA; el carrusel no se mueve solo y anuncia la foto | T015, T018, T025, T027, T028 (`medir.mjs` y teclado), T030 |
| `/galeria` indexable, Lighthouse SEO ≥ 90, JavaScript ≤ 100 kB, contenido en el HTML del servidor | T014, T027, T030 (Lighthouse y K10) |
| Cada foto ≤ 500 kB y las de fuera de pantalla con carga diferida | T010, T014, T015 |
