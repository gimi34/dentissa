---
spec: 051-ciudad-lista-cerrada
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-18
---

# Plan · 051 Ciudad elegida de una lista cerrada

## Enfoque técnico
La lista de ciudades vive en una sola clase de dominio nueva, `App\Pacientes\Ciudades`. Guarda
los 113 municipios de Michoacán y los 4 de Guerrero con su nombre oficial de INEGI, más «Otro
lugar», y sabe normalizar un texto viejo hacia un nombre de la lista. Esa clase la usan:
- un parcial Blade que pinta las opciones en los cuatro formularios;
- una regla de validación nueva, `App\Rules\CiudadDeLaLista`, que sustituye a
  `nullable|string|max:100` en los cinco puntos de entrada (registro, registro tras Google,
  perfil, alta y edición de paciente);
- el directorio, que filtra sus ciudades contra la lista.

La regla acepta una ciudad fuera de la lista solo si es exactamente la que ya tiene guardada ese
paciente, y la lee del servidor, nunca de la petición (CA6, CA10). Las ciudades guardadas se
corrigen con una migración de datos (decisión del usuario). La migración llama a un servicio,
`App\Pacientes\CorreccionCiudades`, que recorre los pacientes por id, compara en PHP para no
depender de la intercalación sin acentos de MariaDB, actualiza por id y deja un evento de
auditoría con los totales. No hay cambio de esquema: `pacientes.ciudad` sigue siendo un
`varchar(100)` opcional.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 051 `approved` y `design.md` `approved`; `tasks.md` antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cambian comportamiento todos los CA salvo CA11. Hoy los formularios tienen `<input type="text">`, el servidor acepta cualquier texto, el filtro ofrece valores sucios y no hay corrección: sus tests fallan antes. CA11 (mostrar como hoy) es de caracterización, comprobada rompiendo la celda «Ciudad» de la ficha y del directorio. Que los nombres coincidan con el catálogo de INEGI se comprueba contra el catálogo y se anota en la tarea (dato de un servicio externo) | test: `CiudadesTest`, `CiudadListaCerradaTest`, `CorreccionCiudadesTest` (rojos antes) · manual: cotejo con el catálogo de INEGI anotado en la tarea de la lista |
| P3 Autorización en el servidor | ✅ | Sin endpoints nuevos. Los cinco que cambian solo cambian su validación de `ciudad`; el rol y el alcance siguen igual: middleware `role:` y `abort(403)` del asistente en la edición, que la 014 convertirá en Gate. La ciudad guardada que admite la regla sale del paciente de la ruta o de la sesión, no de la petición | test: `test_el_asistente_sigue_sin_poder_editar_un_paciente` (caracterización, comprobada rompiendo el `abort(403)`) y `test_la_ciudad_antigua_de_otro_paciente_se_rechaza` (CA6) |
| P4 Entrada validada en servidor y SQL con bindings | ✅ | `CiudadDeLaLista` en los cinco `validate()`. El filtro del directorio solo aplica `?ciudad=` si está en la lista y con binding. La corrección actualiza por id con Query Builder, sin `whereRaw`/`DB::raw` nuevos. En Blade, solo la salida escapada de Blade para la ciudad antigua (opción y aviso) | test: `test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario` (CA5), `test_la_ciudad_antigua_se_muestra_escapada` (TM3) · lint: semgrep `p/php` sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | La lista son nombres públicos de municipios; los tests crean sus datos y no versionan volcados | test: gitleaks en `/implement` y CI |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas. Línea base del 2026-11-18: `composer audit` sin altas ni críticas (código 0); `auditar-npm.mjs` sin avisos altos ni críticos sin excepción (avisa que EX5 ya se puede retirar: fuera de esta spec) | test: job SCA de la CI · manual: salida de las herramientas en `/review` |
| P7 Auditoría de cambios a datos sensibles | ✅ | La corrección masiva registra `pacientes_ciudades_corregidas` (CA12), crítica dentro de la misma transacción. Los cambios de ciudad desde los formularios siguen como hoy (fuera de alcance de la spec) | test: `test_la_correccion_registra_un_evento_con_totales_y_sin_valores` |
| P8 Correlación y logs sin datos sensibles | ✅ | Ningún `Log::` nuevo. El evento lleva el `request_id` del registro central (nulo en la migración) y solo totales, nunca ciudades ni nombres | test: el mismo test de CA12 comprueba que `detalle` solo tiene `corregidos` y `fuera_de_lista`, enteros |
| P9 Solo el sistema de diseño | ✅ | El desplegable copia el de «Género» de cada formulario. El aviso es `.field-hint field-hint-aviso` (`--text`) en un bloque `/* Spec 051 */` de `app.css`, solo con tokens. La variante entra antes en `system.md`/`system.html` (primera tarea). Sin `style=""` nuevo. Etiqueta asociada en el perfil | test: `test_el_bloque_051_solo_usa_tokens` (con `ReglasCss`, control positivo), `test_el_campo_ciudad_es_un_desplegable_con_etiqueta_asociada` · manual: los cuatro formularios a 320, 390 y 1440 px contra `design.md` |
| P10 Rutas indexables descubribles | ➖ | `/registro` conserva ruta, título, descripción y canónica; solo cambia el control de un campo opcional | test: `PaginasPublicasTest` y `TerminosPrivacidadTest` en verde |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Pacientes/Ciudades.php` (nuevo) | Constantes `MICHOACAN` (113) y `GUERRERO` (4), en orden alfabético español (las vocales con tilde se ordenan como sin tilde) y con el nombre oficial de INEGI; `OTRO_LUGAR = 'Otro lugar'`; `ALIAS` (D5). Métodos: `grupos(): array<string, list<string>>`, `nombres(): list<string>` (todas más «Otro lugar»), `contiene(?string): bool` (igualdad exacta), `normalizar(string): ?string` (clave sin mayúsculas, sin tildes y con los espacios colapsados → nombre de la lista o alias; `null` si no coincide) | bajo |
| `app/Rules/CiudadDeLaLista.php` (nuevo) | `ValidationRule` con `__construct(?string $guardada = null)`: pasa si el valor está en `Ciudades::nombres()` o si es idéntico a `$guardada` y `$guardada` no está en la lista. Mensaje «Elige una ciudad de la lista». El vacío lo resuelve `nullable` (D10) | bajo |
| `app/Pacientes/CorreccionCiudades.php` (nuevo) | `ejecutar(): array{corregidos:int, fuera_de_lista:int}`. Recorre `pacientes` con `chunkById` (`id`, `ciudad`). Por cada ciudad no vacía que no esté en la lista, la normaliza: si sale un nombre, `update` por `id`; si no, cuenta fuera de la lista. Todo en `DB::transaction` con `Auditoria::registrar('pacientes_ciudades_corregidas', 'exito', 'pacientes', campos: ['ciudad'], detalle: [...], critico: true)` al final | medio: toca datos de todos los pacientes |
| `database/migrations/2026_11_18_000000_corregir_ciudades_de_pacientes.php` (nuevo) | `up()` → `app(CorreccionCiudades::class)->ejecutar()`; `down()` vacío con un comentario: la corrección no se deshace (Rollout) | medio |
| `resources/views/partials/opciones-ciudad.blade.php` (nuevo) | Recibe `$seleccionada` y `$guardada`. Si `$guardada` no está en la lista, la pinta primero como «<valor> (no está en la lista)», elegida si coincide con `$seleccionada`. Después «Sin indicar» (`value=""`), `<optgroup label="Michoacán">`, `<optgroup label="Guerrero">` y «Otro lugar» | bajo |
| `app/Http/Controllers/RegisterController.php` | `register` y `contactoStore`: `'ciudad' => ['nullable', new CiudadDeLaLista]` | bajo |
| `app/Http/Controllers/PerfilController.php` | `update`: se lee el paciente antes de validar y se pasa `new CiudadDeLaLista($paciente?->ciudad)`; `edit` sin cambios (la vista recibe `$paciente`) | bajo |
| `app/Http/Controllers/PacientesController.php` | `store`: `new CiudadDeLaLista`; `update`: `new CiudadDeLaLista($paciente->ciudad)`, con el paciente ya cargado antes de `validate()`. `index`: `$ciudades` filtradas con `Ciudades::contiene()` en PHP, `$totalCiudades` sin «Otro lugar» y `?ciudad=` aplicado solo si está en la lista (D6) | medio: el directorio lo usan tres roles |
| `resources/views/auth/register.blade.php`, `auth/registro-contacto.blade.php` | El `<input>` de ciudad pasa a `<select id="ciudad" name="ciudad" class="field">` dentro del mismo `.field-wrap` con su icono, como «Género», con el parcial; sin placeholder; error `@error('ciudad')` con `.error-msg` | bajo |
| `resources/views/cliente/perfil.blade.php` | `<label for="campo-ciudad">` y `<select id="campo-ciudad">` con el parcial; error `.field-hint field-hint-error`; aviso `.field-hint field-hint-aviso` con `id` en `aria-describedby` si la guardada está fuera de la lista | bajo |
| `resources/views/pacientes/create.blade.php` (alta y edición, que la incluye) | `<select>` con el patrón de «Género» (`field-error`, `.error-msg`, `aria-invalid`, `aria-describedby`); aviso solo en la edición con ciudad fuera de la lista; `aria-describedby` con error y aviso si hay los dos | bajo |
| `resources/views/pacientes/index.blade.php` | «Ciudades distintas» con `$totalCiudades` | bajo |
| `public/css/app.css` | Bloque `/* Spec 051 */`: `.field-hint-aviso { color: var(--text); }` | bajo |
| `docs/design/system.md`, `docs/design/system.html` | Variante «Aviso de campo» en «Campo de formulario», versión 1.20.0, con su fila en «Decisiones» | bajo |
| `tests/Feature/MovilPacientesExpedientesTest.php` | La comprobación de «se conserva lo escrito» del alta y la edición pasa de `input[name=ciudad]` a `contacto_emergencia` (sigue siendo texto libre); la de error con `ciudad` de 101 caracteres se queda (ahora falla por no estar en la lista); el `select` de ciudad se mira en el test nuevo | bajo |

## Contratos y datos
- **Formularios (HTML):** `ciudad` sigue con el mismo nombre en los cinco `POST`/`PUT`. Valores
  aceptados: `""` (sin ciudad), un nombre de `Ciudades::nombres()`, o, solo en perfil y edición,
  la ciudad guardada idéntica. Respuesta ante un valor no válido: la de siempre de `validate()`
  (redirección atrás con `errors` y el resto de `old()`), con el mensaje «Elige una ciudad de la
  lista».
- **Directorio:** `GET …/pacientes?ciudad=<valor>`. Un valor fuera de la lista se ignora y lista
  todos (D6); `buscar` sigue encontrando ciudades antiguas por texto.
- **Datos:** sin cambio de esquema. La migración de datos reescribe `pacientes.ciudad` de las
  filas cuya ciudad se normaliza a un nombre distinto del guardado. Es idempotente: una segunda
  pasada encuentra todas en la lista o sin coincidencia y no actualiza nada. Su evento sí se
  registra cada vez, con `corregidos: 0`.
- **Auditoría:** acción `pacientes_ciudades_corregidas`, recurso `pacientes` (sin id), `campos:
  ['ciudad']`, `detalle: {corregidos: int, fuera_de_lista: int}`, actor nulo (sistema).
- **Pantallas y estados** (según `design.md`):

  | Pantalla | Estados de «Ciudad» |
  |---|---|
  | Registro con correo y registro tras Google | vacío («Sin indicar»), elegido, error CA5 |
  | Perfil | vacío, elegido, guardada fuera de la lista (opción marcada y aviso), error CA5/CA6 |
  | Alta de paciente | vacío, elegido, error |
  | Edición de paciente | vacío, elegido, guardada fuera de la lista (opción marcada y aviso), error, error con aviso |
  | Directorio | filtro con las ciudades de la lista presentes y «Otro lugar» si lo hay |

  Accesibilidad medible: etiqueta asociada (`for`/`id`) en los cuatro formularios; error y aviso
  en `aria-describedby`; contraste del aviso `--text` sobre `--white` 21:1; toque ≥ 44 px a 320 y
  390 px (`movil.css` ya lo da a `select`).
- **Qué ve cada rol:** sin cambios de permisos.

## Estrategia de pruebas
PHPUnit con `DatabaseTransactions` contra `dentissa_test` (P2), dentro del contenedor.
- **Unit** `tests/Unit/CiudadesTest.php`: 113 + 4 nombres sin repetir; orden alfabético por clave
  sin tildes dentro de cada grupo; «Otro lugar» al final de `nombres()`; `normalizar()` con
  mayúsculas, tildes, espacios dobles y finales, cada alias de D5 y textos sin coincidencia
  («LC», «zihuayork», «colima» → `null`); `normalizar(normalizar(x))` estable.
- **Feature** `tests/Feature/CiudadListaCerradaTest.php`, con las cuentas de los roles:
  - **marcado:** de los cuatro formularios (`select[name=ciudad]` con `optgroup`, primera opción
    «Sin indicar», última «Otro lugar», etiqueta asociada, sin `input[name=ciudad]`);
  - **guardar y rechazar:** guardar un nombre de la lista y «Sin indicar» en los cinco puntos de
    entrada; rechazo de «Ciudad de México», de un texto de 101 caracteres y de `<b>x</b>` en cada
    uno, con el mensaje, sin cambios en la BD y con `old()` del resto;
  - **ciudad antigua:** CA6 con la ciudad antigua de otro paciente; CA10 con la opción marcada,
    el aviso, guardar sin cambiarla (se conserva) y cambiándola (se sustituye); TM3 con una
    ciudad antigua `<script>` escapada en la opción y en el aviso;
  - **directorio:** el filtro, `?ciudad=` fuera de la lista ignorado y «Ciudades distintas»;
  - **caracterización:** CA11 (ficha y directorio muestran la ciudad guardada) y el 403 del
    asistente en la edición;
  - **CSS:** `test_el_bloque_051_solo_usa_tokens`.
- **Feature** `tests/Feature/CorreccionCiudadesTest.php`: inserta pacientes con «lazaro
  cardenas», «MORELIA », «Zihuatanejo», «La Unión», «LC» y «Petatlán», ejecuta el servicio y
  comprueba:
  - las cuatro primeras corregidas, «LC» intacta y «Petatlán» sin tocar;
  - una segunda ejecución sin cambios;
  - el evento con `corregidos` y `fuera_de_lista` relativos a la línea base de la BD de tests, y
    sin valores;
  - con un almacén de auditoría que falla, nada se corrige (transacción).

  La migración se prueba indirectamente (solo llama al servicio): `migrate` sobre la BD de tests
  la ejecuta en la CI.
- Existentes que deben seguir en verde: `MovilPacientesExpedientesTest` (ajustado),
  `TerminosPrivacidadTest`, `PaginasPublicasTest`, `NavegacionInternaTest`, `MovilPersonalTest`,
  `PerfilCambioCorreoTest` y la suite completa. Los tests de vistas son de servidor (sin
  navegador), así que no aplica la doble ejecución.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: petición alterada con una ciudad fuera de la lista (texto largo, marcado) en registro, perfil, alta o edición | A06:2025 Insecure Design | los cinco `validate()` | `CiudadDeLaLista` en el servidor, independiente del desplegable | `test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario` (CA5) |
| TM2 | Tampering: reutilizar la ciudad antigua de otro paciente para colar un valor fuera de la lista | A06:2025 Insecure Design | `CiudadDeLaLista($guardada)` en perfil y edición | `$guardada` sale del paciente de la sesión (perfil) o de la ruta (edición), nunca de la petición; comparación exacta | `test_la_ciudad_antigua_de_otro_paciente_se_rechaza` (CA6) |
| TM3 | Stored XSS: una ciudad antigua con marcado se pinta en la opción y en el aviso | A05:2025 Injection | `partials/opciones-ciudad`, perfil, edición | solo salida escapada de Blade, sin salida sin escapar | `test_la_ciudad_antigua_se_muestra_escapada` |
| TM4 | Tampering: `?ciudad=` del directorio con un valor arbitrario | A05:2025 Injection | `PacientesController::index` | binding de Query Builder y solo si `Ciudades::contiene()` | `test_el_filtro_ignora_una_ciudad_fuera_de_la_lista` |
| TM5 | Repudiation: la corrección masiva cambia datos de pacientes sin rastro | A09:2025 Security Logging and Alerting Failures | `CorreccionCiudades` | evento crítico dentro de la transacción; si no se escribe, no se corrige nada | `test_la_correccion_registra_un_evento_con_totales_y_sin_valores`, `test_si_la_auditoria_falla_no_se_corrige_nada` |
| TM6 | Information disclosure: el evento o un log con ciudades de pacientes | A09:2025 Security Logging and Alerting Failures | `CorreccionCiudades` | `detalle` solo con dos enteros; la validación de `Auditoria` rechaza texto libre | `test_la_correccion_registra_un_evento_con_totales_y_sin_valores` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: lista en los dos registros | `Ciudades`, `partials/opciones-ciudad`, `auth/register`, `auth/registro-contacto` | Feature: `test_el_registro_ofrece_la_lista_de_ciudades` (con correo y tras Google) · Unit: `test_la_lista_tiene_113_de_michoacan_y_4_de_guerrero_en_orden` · verificación manual: los dos registros a 320, 390 y 1440 px contra `design.md` |
| CA2: lista en el perfil con la guardada elegida | `PerfilController`, `cliente/perfil` | Feature: `test_el_perfil_ofrece_la_lista_con_la_ciudad_guardada` · verificación manual: perfil a 320, 390 y 1440 px |
| CA3: lista en alta y edición | `pacientes/create` | Feature: `test_el_alta_y_la_edicion_ofrecen_la_lista` · verificación manual: alta y edición a 320, 390 y 1440 px |
| CA4: se guarda el nombre o queda sin ciudad | los cinco `validate()` | Feature: `test_cada_formulario_guarda_una_ciudad_de_la_lista`, `test_sin_indicar_deja_al_paciente_sin_ciudad` |
| CA5 (abuso): fuera de la lista se rechaza | `CiudadDeLaLista` | Feature: `test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario` |
| CA6 (abuso): ciudad antigua de otro paciente | `CiudadDeLaLista($guardada)` | Feature: `test_la_ciudad_antigua_de_otro_paciente_se_rechaza` |
| CA7: filtro limpio y «Ciudades distintas» | `PacientesController::index`, `pacientes/index` | Feature: `test_el_filtro_solo_ofrece_ciudades_de_la_lista`, `test_ciudades_distintas_no_cuenta_otro_lugar` |
| CA8: corrección de las que coinciden, idempotente | `CorreccionCiudades`, migración | Feature: `test_la_correccion_arregla_las_que_coinciden`, `test_la_correccion_dos_veces_no_cambia_nada` · Unit: `test_normalizar_*` |
| CA9: las que no coinciden se conservan | `CorreccionCiudades` | Feature: `test_la_correccion_conserva_las_que_no_coinciden` |
| CA10: aviso y conservar o sustituir | `partials/opciones-ciudad`, perfil, edición, `CiudadDeLaLista($guardada)` | Feature: `test_la_ciudad_fuera_de_la_lista_muestra_el_aviso`, `test_guardar_sin_cambiar_conserva_la_ciudad_antigua`, `test_elegir_otra_sustituye_la_ciudad_antigua` · verificación manual: el aviso en perfil y edición a 390 y 1440 px |
| CA11: se muestra como hoy | sin cambio (`pacientes/show`, `pacientes/index`) | Feature (caracterización): `test_la_ficha_y_el_directorio_muestran_la_ciudad_guardada`, comprobada rompiendo la celda |
| CA12: evento de la corrección | `CorreccionCiudades` | Feature: `test_la_correccion_registra_un_evento_con_totales_y_sin_valores` |
| TM1: ciudad fuera de la lista en una petición alterada | `CiudadDeLaLista` | Feature: `test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario` |
| TM2: ciudad antigua de otro paciente | `CiudadDeLaLista($guardada)` | Feature: `test_la_ciudad_antigua_de_otro_paciente_se_rechaza` |
| TM3: ciudad antigua con marcado | `partials/opciones-ciudad`, perfil, edición | Feature: `test_la_ciudad_antigua_se_muestra_escapada` |
| TM4: `?ciudad=` arbitrario en el directorio | `PacientesController::index` | Feature: `test_el_filtro_ignora_una_ciudad_fuera_de_la_lista` |
| TM5: corrección sin rastro | `CorreccionCiudades` | Feature: `test_la_correccion_registra_un_evento_con_totales_y_sin_valores`, `test_si_la_auditoria_falla_no_se_corrige_nada` |
| TM6: ciudades en el evento | `CorreccionCiudades` | Feature: `test_la_correccion_registra_un_evento_con_totales_y_sin_valores` |
| RNF: WCAG 2.1 AA en los campos tocados | vistas, `app.css` | Feature: `test_el_campo_ciudad_es_un_desplegable_con_etiqueta_asociada`, `test_el_error_y_el_aviso_van_en_aria_describedby` · verificación manual: foco visible y lector de pantalla en perfil y edición |
| RNF: elegir tecleando las primeras letras | desplegable nativo, sin JavaScript | verificación manual: con teclado, «Laz» salta a Lázaro Cárdenas en los cuatro formularios |
| RNF: descubribilidad del registro sin cambios | — | Feature: `PaginasPublicasTest` en verde |
| RNF: la misma lista en formularios y validación | `Ciudades` usada por el parcial y la regla | Feature: `test_cada_opcion_del_formulario_se_acepta_al_guardar` (recorre las opciones del HTML) |

## Observabilidad
- Logs nuevos: ninguno. Si el almacén de auditoría falla, la línea `auditoria.fallo` existente
  del canal `seguridad` (sin valores) y la migración falla.
- Eventos de auditoría: `pacientes_ciudades_corregidas` → `CorreccionCiudades::ejecutar()`, al
  final de la transacción, en modo crítico → `test_la_correccion_registra_un_evento_con_totales_y_sin_valores`.
- Métricas y alertas: sin cambios.
- Cómo se verifica tras el deploy: en la pantalla de auditoría de la administración, filtrar por
  la acción `pacientes_ciudades_corregidas` y ver un evento con los totales de producción; abrir
  el directorio y comprobar que el filtro solo ofrece ciudades de la lista.

## Descubribilidad
No aplica: no se crean ni cambian rutas públicas. `/registro` conserva título, descripción,
canónica e indexación; solo cambia el control de un campo opcional (`PaginasPublicasTest` en verde).

## Rollout
- Feature flag: no aplica. El cambio es de validación y de un control de formulario, sin riesgo
  sobre el acceso, y la corrección es idempotente y solo cosmética. Volver atrás es el rollback.
- Orden de despliegue: `deploy/desplegar.sh vX.Y.Z` ya hace `migrate --force` con la imagen nueva
  (la corrección y su evento) antes de `up -d app web`. Antes, la copia de la base de siempre.
- Compatibilidad: durante la ventana entre `migrate` y el cambio de contenedores, la versión
  anterior aún acepta texto libre. Una ciudad escrita en esa ventana queda como antigua y la trata
  CA10. Las ciudades corregidas son válidas para la versión anterior.
- Rollback: `deploy/volver.sh <tag anterior>`. La migración no se deshace (`down()` vacío): solo
  reescribió la forma de ciudades que ya eran ese municipio. Si hiciera falta el valor original,
  está en la copia de la base previa al despliegue.
- Métricas a vigilar: errores 500 en registro, perfil y pacientes (logs de la app); que el evento
  de auditoría exista y su `fuera_de_lista`, que dice cuántos pacientes verán el aviso.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Decisión | Opciones | Elegida y motivo |
|---|---|---|---|
| D1 | Dónde vive la lista | constantes en una clase de dominio · `config/ciudades.php` · tabla en la BD | **Clase `App\Pacientes\Ciudades`**: versionada y testeable, con la normalización al lado y sin migración de esquema. Una tabla solo haría falta para editarla desde la app, que está fuera de alcance. Sigue el patrón de `app/Agenda/` y `app/Reportes/`; no es arquitectónica (sin ADR) |
| D2 | Cómo se valida | `Rule::in()` más un cierre en cada controlador · regla propia | **Regla `App\Rules\CiudadDeLaLista`**: un solo sitio para cinco entradas, con la excepción de la ciudad guardada (CA10). Sigue el patrón de `HoraFinPosterior` |
| D3 | Cómo se corrigen las guardadas | migración de datos · comando artisan a mano | **Migración** (decisión del usuario, 2026-11-18): corre sola en el despliegue, una vez, idempotente |
| D4 | Dónde se compara | en SQL · en PHP por id | **PHP por id**: la intercalación de `pacientes.ciudad` no distingue mayúsculas ni tildes, así que `WHERE ciudad = ?` o `DISTINCT` mezclarían «zihuatanejo» con «Zihuatanejo». Comparar en PHP y actualizar por id evita `whereRaw('BINARY …')`, prohibido por P4 |
| D5 | Alias de la normalización | ninguno · los nombres cortos de uso común | **Nombres cortos** (supuesto de la spec): «Zihuatanejo» → Zihuatanejo de Azueta; «La Unión» → La Unión de Isidoro Montes de Oca; «Coahuayutla» → Coahuayutla de José María Izazaga; «Coalcomán» → Coalcomán de Vázquez Pallares; «Cojumatlán» → Cojumatlán de Régules; «Tiquicheo» → Tiquicheo de Nicolás Romero; «Ciudad Hidalgo» → Hidalgo; «Los Reyes de Salgado» → Los Reyes. Nada más, para no adivinar. En la BD local, «Zihuatanejo» (7 pacientes) es el caso más común |
| D6 | `?ciudad=` fuera de la lista en el directorio | aplicarlo · ignorarlo | **Ignorarlo** (aprobado con el plan, 2026-11-18): el filtro solo ofrece la lista (CA7). Una ciudad antigua se sigue encontrando con «Buscar» |
| D7 | Auditoría de la corrección | no crítica, tras corregir · crítica, dentro de la transacción | **Crítica dentro de la transacción** (aprobado con el plan, 2026-11-18): si no queda constancia, no se corrige y la migración falla, así que el despliegue se detiene con la copia previa a mano (TM5) |
| D8 | Orden con otras specs | 051 primero · después de la 050 · en paralelo | **Después de la 050** (decisión del usuario, 2026-11-18). La 050 cambia la regla de `fecha_nacimiento` en los mismos tres controladores y el `max` de fecha en las mismas vistas. Antes de `/implement`, la rama se pone al día con `main`; los choques son de líneas vecinas. La 014 (`approved`, sin implementar) también tocará `PacientesController` (alcance del listado y Gates): cuando llegue, el filtro de CA7 hereda su alcance sin cambios en esta spec |
| D9 | Fuente de los nombres | catálogo de INEGI (AGEEML / Marco Geoestadístico) · una lista de internet | **INEGI**: la tarea de la lista coteja los 113 nombres de Michoacán (clave 16) y los 4 de Guerrero (clave 12) con el catálogo y anota el cotejo (P2, dato externo) |
| D10 | Valor de «Sin indicar» | `""` · un valor propio | **`""`**: el middleware `ConvertEmptyStringsToNull` lo hace `null` y `nullable` lo acepta, como hoy |
| D11 | Feature flag | sí · no | **No**: ver Rollout |

## Impacto en arquitectura
- `docs/architecture.md`: nuevo módulo `app/Pacientes/` (`Ciudades`, `CorreccionCiudades`) y la
  regla `CiudadDeLaLista` en la sección de validación; la migración de datos en la lista de
  migraciones.
- `AGENTS.md` → «Mapa del repositorio»: fila de `app/Pacientes/` y `app/Rules/` con la regla
  nueva.
- `docs/observability.md`: fila del evento `pacientes_ciudades_corregidas` en la tabla de eventos.
- `docs/design/system.md` y `system.html`: variante «Aviso de campo» (primera tarea, no T090).
- Specs extendidas (002, 006, 011): su Historial lo anota `/release`.

## Riesgos y mitigaciones
- **Un nombre mal escrito en la lista** (tilde, «de» de más) deja fuera a pacientes reales o
  impide corregirlos. Mitigación: cotejo con INEGI (D9) y test de recuento y sin repetidos.
- **La migración llama a código de la app**: si `Ciudades` cambia después, una BD nueva que
  ejecute las migraciones desde cero usará la lista nueva. Es aceptable, porque la corrección
  depende justo de la lista vigente; queda dicho en un comentario de la migración.
- **Choques con la 050** en los mismos archivos. Mitigación: D8, ponerse al día con `main` antes
  de `/implement`.
- **El volcado `database/dentissa.sql` (ruta protegida)** tiene ciudades de prueba sucias. No se
  toca: la migración las corrige al cargar la BD, también en la de tests.
- **Desplegable de 119 opciones en el móvil:** el selector nativo del sistema lo maneja con
  desplazamiento y búsqueda por letra; se valida a mano a 320 y 390 px.
