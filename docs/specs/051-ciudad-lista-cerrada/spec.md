---
id: 051
slug: ciudad-lista-cerrada
status: approved
created: 2026-11-18
extends: [002, 006, 011]
---

# 051 · Ciudad elegida de una lista cerrada

## Problema
Hoy la ciudad del paciente se escribe a mano en el registro, en su perfil y en el alta o la edición
que hace el personal, y se acepta cualquier texto. Por eso la misma ciudad aparece escrita de varias
formas («Lázaro Cárdenas», «lazaro cardenas», «LC») o con valores inventados, y el filtro por
ciudad del directorio de pacientes ofrece esa mezcla, lo que lo hace poco útil para el personal. Si
no se corrige, cada alta nueva ensucia más el dato y el filtro deja de servir.

## Historias de usuario
- Como paciente que se registra o actualiza su perfil, quiero elegir mi ciudad de una lista para no
  tener que escribirla y no equivocarme.
- Como personal de la clínica (administración, dentista o asistente), quiero que la ciudad de cada
  paciente salga de una lista fija para que el filtro por ciudad del directorio agrupe bien a los
  pacientes.
- Como personal, quiero que las ciudades ya guardadas se corrijan cuando sea posible y que se me
  avise de las que no, para limpiar el dato sin perder lo que ya había.

## Criterios de aceptación
- [x] CA1 · Dado un visitante en cualquiera de los dos formularios de registro (con correo y
  contraseña, y el de datos de contacto tras entrar con Google), cuando llega al campo «Ciudad»,
  entonces solo puede elegir de una lista: «Sin indicar» (por defecto), los 113 municipios de
  Michoacán, los municipios de Guerrero La Unión de Isidoro Montes de Oca, Zihuatanejo de Azueta,
  Petatlán y Coahuayutla de José María Izazaga, y «Otro lugar» al final; los municipios van
  agrupados por estado y en orden alfabético dentro de cada grupo, y no se puede escribir texto
  libre.
- [x] CA2 · Dado un paciente en su perfil, cuando abre el formulario, entonces el campo «Ciudad» es
  la misma lista de CA1 y aparece elegida la ciudad que tiene guardada (o «Sin indicar» si no tiene).
- [x] CA3 · Dado personal que puede dar de alta o editar pacientes, cuando abre el alta o la edición
  de un paciente, entonces el campo «Ciudad» es la misma lista de CA1 y, en la edición, aparece
  elegida la ciudad guardada.
- [x] CA4 · Dado cualquiera de esos formularios, cuando se guarda con un valor de la lista,
  entonces se guarda exactamente ese nombre (p. ej. «Lázaro Cárdenas»); con «Sin indicar», el
  paciente queda sin ciudad; la ciudad sigue siendo opcional.
- [x] CA5 · (abuso) Dado cualquiera de esos formularios, cuando alguien envía una ciudad que no está
  en la lista (alterando el formulario o enviando la petición a mano), entonces se rechaza con el
  mensaje «Elige una ciudad de la lista» junto al campo, no se guarda nada de ese envío y el
  formulario conserva los demás datos escritos.
- [x] CA6 · (abuso) Dado un paciente con una ciudad antigua fuera de la lista, cuando alguien envía
  en otro formulario (otro paciente o un alta nueva) esa misma ciudad antigua, entonces se rechaza
  como en CA5: una ciudad fuera de la lista solo se acepta para conservar la que ya tenía ese mismo
  paciente (CA10).
- [x] CA7 · Dado el directorio de pacientes, cuando el personal abre el filtro por ciudad,
  entonces solo ofrece ciudades de la lista que tiene al menos un paciente que esa persona puede
  ver, más «Otro lugar» si hay alguno; nunca ofrece valores fuera de la lista. La cifra «Ciudades
  distintas» cuenta esas mismas ciudades sin contar «Otro lugar».
- [x] CA8 · Dada una ciudad ya guardada que coincide con un nombre de la lista sin importar
  mayúsculas, acentos ni espacios sobrantes (p. ej. «lazaro cardenas», «MORELIA », «Zihuatanejo»
  para Zihuatanejo de Azueta, «La Unión» para La Unión de Isidoro Montes de Oca), cuando se libera
  esta spec, entonces esa ciudad queda guardada con el nombre de la lista; aplicar la corrección dos
  veces deja el mismo resultado.
- [x] CA9 · Dada una ciudad ya guardada que no coincide con ninguna de la lista (p. ej. «LC»),
  cuando se libera esta spec, entonces se conserva sin cambios y la ficha del paciente la sigue
  mostrando como hoy.
- [x] CA10 · Dado un paciente con una ciudad fuera de la lista (CA9), cuando él abre su perfil o el
  personal abre su edición, entonces el formulario muestra el aviso «La ciudad guardada «<valor>» no
  está en la lista: elige una o deja «Sin indicar»» junto al campo, con esa ciudad elegida como
  opción marcada «no está en la lista»; si se guarda sin cambiarla, se conserva tal cual, y si se
  elige otra opción, la sustituye y la antigua deja de ofrecerse.
- [x] CA11 · Dado un paciente con «Otro lugar» o con una ciudad de la lista, cuando el personal abre
  su ficha o el directorio, entonces la ciudad se muestra como hoy, con el nombre tal como está
  guardado.

## Fuera de alcance
- Añadir, quitar o renombrar municipios desde la aplicación: la lista es fija y cambiarla es otra
  spec o un mantenimiento.
- Localidades, colonias o rancherías (p. ej. La Mira o Guacamayas): solo municipios.
- Un campo de estado o país aparte, o pedir en qué lugar vive quien elige «Otro lugar».
- Un buscador con sugerencias mientras se escribe dentro del campo.
- Corregir a mano, en bloque, las ciudades que no coinciden (CA9): se corrigen una por una al
  editar al paciente (CA10).
- Cambiar quién puede ver o editar la ciudad de un paciente: sigue como hoy.
- Ampliar el registro de auditoría de los cambios de pacientes hechos desde los formularios: sigue
  como hoy; esta spec solo registra la corrección de las ciudades guardadas (CA12).
- La dirección y los demás campos de texto libre del paciente.

## Seguridad y privacidad
- Datos sensibles involucrados: la ciudad forma parte de los datos de contacto del paciente,
  confidencial (personal) según `docs/security.md`; no toca datos de salud ni credenciales.
- Quién puede hacer qué: igual que hoy. El visitante elige su ciudad al registrarse; el paciente
  cambia la suya en su perfil; el personal la indica en el alta y la edición de los pacientes que ya
  puede dar de alta o editar. Nadie gana ni pierde permisos.
- Casos de abuso:
  - Como usuario malintencionado, envío una ciudad que no está en la lista (texto largo, marcado,
    otro idioma) saltándome el desplegable → se rechaza y no se guarda nada (CA5).
  - Como usuario malintencionado, reutilizo la ciudad antigua de otro paciente para colar un valor
    fuera de la lista → se rechaza (CA6).

## Auditoría
- [x] CA12 · Dada la corrección de las ciudades guardadas (CA8 y CA9), cuando termina, entonces
  queda registrado un evento con actor «sistema», la acción, el recurso «pacientes», el resultado y
  cuántos pacientes se corrigieron y cuántos quedaron fuera de la lista, sin nombres de pacientes ni
  valores de ciudad (ver `docs/observability.md`).

## Diseño
Quien llena el formulario debe encontrar su municipio rápido y sin dudar, con el mismo aspecto que
el resto de los campos; el cambio no debe notarse más que en que ya no se escribe. Sistema:
[docs/design/system.md](../../design/system.md).

Pantallas que toca: registro con correo, datos de contacto tras Google, perfil del paciente, alta y
edición de paciente del personal y el filtro por ciudad del directorio, en móvil y escritorio. Qué
debe diseñarse: el campo de lista con sus grupos por estado, la opción «Otro lugar», el estado con
ciudad antigua fuera de la lista y su aviso (CA10) y el error de CA5, con los componentes de campo y
aviso que ya existen. Sin recursos de marca nuevos.

Sin ciclo de candidatos de `/design-spec`: `/plan` usa los componentes del sistema y el usuario lo
valida en pantalla durante `/implement`. Acta: [design.md](design.md).

## Criterios de diseño
Modo: operar · sin ciclo de candidatos · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Campo «Ciudad» en los cuatro formularios | mismo aspecto que los demás campos del formulario; etiqueta visible asociada al campo | CA1–CA3 |
| K2 | Error y aviso del campo | texto visible junto al campo, anunciado al lector de pantalla y no solo con color | CA5, CA10 |
| K3 | Formularios y filtro a 320, 390 y 1440 px | sin desplazamiento lateral; el nombre más largo de la lista se lee completo o se corta sin desbordar | CA1–CA3, CA7 |
| K4 | Teclado | se llega al campo y se elige un municipio solo con teclado, tecleando sus primeras letras | CA1–CA3 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible,
toque ≥ 44 px en el móvil, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad WCAG 2.1 AA en los campos tocados (constitución P9): etiqueta asociada, foco
  visible, error y aviso con texto y anunciados al lector de pantalla.
- Quien usa el teclado elige un municipio tecleando sus primeras letras, sin recorrer la lista.
- Descubribilidad: el registro sigue indexable con el mismo título, descripción y vista previa al
  compartir; no se crean ni cambian rutas públicas (`docs/seo.md`).
- La lista es la misma en los cuatro formularios y en la validación: un municipio aceptado en uno
  se acepta en todos.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-18 | brecha | ¿Qué restricción lleva el campo «Ciudad»? | Lista cerrada: solo se acepta lo que está en la lista, sin texto libre | usuario |
| 2026-11-18 | brecha | ¿Qué ciudades incluye la lista? | Municipios de Michoacán y los vecinos de Guerrero | usuario |
| 2026-11-18 | brecha | ¿Qué municipios de Guerrero son «vecinos»? | Costa Grande cercana: La Unión de Isidoro Montes de Oca, Zihuatanejo de Azueta, Petatlán y Coahuayutla de José María Izazaga | usuario |
| 2026-11-18 | brecha | ¿Qué elige quien vive fuera de la región? | La opción «Otro lugar», al final de la lista | usuario |
| 2026-11-18 | brecha | ¿Qué pasa con las ciudades ya guardadas? | Las que coinciden se corrigen solas; las demás se conservan, el filtro no las ofrece y el formulario avisa al editar | usuario |
| 2026-11-18 | implícita | ¿La lista se comprueba también al guardar, no solo en la pantalla? | Sí: toda entrada se valida en el servidor (CA5, CA6) | `docs/constitution.md` P4 |
| 2026-11-18 | implícita | ¿Qué pacientes cuentan para el filtro por ciudad? | Los que la persona ya puede ver en el directorio, según su alcance | `docs/specs/014-autorizacion-alcance-clinico/spec.md` |
| 2026-11-18 | implícita | ¿Cómo se clasifica la ciudad? | Dato de contacto, confidencial (personal) | `docs/security.md` |
| 2026-11-18 | implícita | ¿La corrección de ciudades guardadas se registra? | Sí, como cambio de datos de pacientes, sin valores (CA12) | `docs/constitution.md` P7, `docs/observability.md` |
| 2026-11-18 | implícita | ¿El registro cambia para los buscadores? | No: misma ruta, título y descripción | `docs/seo.md` |
| 2026-11-18 | supuesto | ¿La etiqueta cambia a «Municipio»? | Se queda «Ciudad», como hoy y como en el aviso de privacidad | agente |
| 2026-11-18 | supuesto | ¿Cómo se ordena la lista? | «Sin indicar» primero; grupo Michoacán y grupo Guerrero, alfabéticos; «Otro lugar» al final | agente |
| 2026-11-18 | supuesto | ¿Qué cuenta como coincidencia al corregir? | Igual sin importar mayúsculas, acentos ni espacios sobrantes, más los nombres cortos de uso común de los municipios de nombre largo (p. ej. «Zihuatanejo», «La Unión», «Coahuayutla»); nada más | agente |
| 2026-11-18 | supuesto | Al editar un paciente con ciudad fuera de la lista, ¿guardar sin tocarla la borra? | No: se conserva tal cual hasta que alguien elija otra opción (CA10), para no perder el dato al cambiar otro campo | agente |
| 2026-11-18 | supuesto | ¿«Otro lugar» cuenta como ciudad en «Ciudades distintas»? | No; sí aparece en el filtro si algún paciente la tiene | agente |
| 2026-11-18 | brecha | La spec tiene 12 criterios (más de ~10): ¿dividirla? | Se mantiene junta: es una sola capacidad y la corrección de datos no tiene sentido sin la lista | usuario |
| 2026-11-18 | diseño | ¿Cómo se diseña el campo? | Sin ciclo de candidatos: desplegable igual al de «Género» en cada formulario y aviso de CA10 con el texto de ayuda en `--text` ([design.md](design.md)) | /design-spec 051 |

## Notas para /plan
- Formularios y controladores que mencionó el usuario: `auth/register.blade.php` y
  `auth/registro-contacto.blade.php` (`RegisterController`), `cliente/perfil.blade.php`
  (`PerfilController`) y `pacientes/create.blade.php` (`PacientesController@store` y `@update`);
  hoy todos validan `ciudad` como `nullable|string|max:100`. El filtro y la cifra salen de
  `PacientesController@index`.
- Fuente de los nombres: el catálogo oficial de municipios de INEGI (Marco Geoestadístico), con sus
  nombres oficiales y acentos.
- La lista debe vivir en un solo sitio que usen las vistas y la validación (último RNF).
- La corrección de datos existentes no debe tocar `database/dentissa.sql` (ruta protegida).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-18 | Creación | Petición del usuario: restringir la ciudad, que hoy acepta cualquier texto |
| 2026-11-18 | Aprobada | Decisión del usuario, con los supuestos revisados |
| 2026-11-18 | Implementada | Tareas T001–T031 y T090–T092 cerradas; suite completa en verde (1497 tests) |
