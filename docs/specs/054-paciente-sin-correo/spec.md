---
id: 054
slug: paciente-sin-correo
status: implemented
created: 2026-11-20
extends: [006]
---

# 054 · Alta de pacientes sin correo

## Problema
Hoy el personal no puede dar de alta a un paciente sin indicar un correo, y cada alta crea una
cuenta con acceso a la aplicación. Hay personas que no tienen correo o prefieren no darlo, y muchas
llegan a la clínica en persona para una sola consulta: para ellas una cuenta no tiene sentido. El
personal acaba inventando correos o no registrando al paciente, lo que ensucia el directorio y deja
citas y expedientes sin ficha fiable.

## Historias de usuario
- Como asistente o admin, quiero registrar a un paciente que no tiene o no quiere dar correo, para
  atenderlo sin crearle un acceso que no va a usar.
- Como asistente o admin, quiero que se me avise si ya existe un paciente con los mismos datos, para
  no duplicar fichas ahora que el correo ya no identifica a nadie.
- Como personal, quiero ver de un vistazo qué pacientes no tienen correo ni acceso, para saber cómo
  contactarles.
- Como admin o dentista, quiero añadir el correo más tarde a un paciente que no lo tenía, para que
  pueda entrar a la aplicación si un día lo desea.

## Criterios de aceptación
- [x] CA1 · Dado un admin o asistente en el alta de paciente, cuando deja el correo vacío (o solo
  con espacios) y llena nombre, apellido y teléfono, entonces el paciente queda registrado, aparece
  en el directorio y se le pueden agendar citas y crear expedientes como a cualquier otro; no se
  genera ni se muestra ninguna contraseña y la confirmación dice «Paciente registrado sin correo: no
  tiene acceso a la aplicación.». Con correo, el alta se comporta como hoy (correo único,
  contraseña indicada o generada y mostrada una sola vez, teléfono opcional).
- [x] CA2 · Dado un alta sin correo y sin teléfono, cuando se guarda, entonces se rechaza sin crear
  nada, con el mensaje «Sin correo, el teléfono es obligatorio para poder contactarle.» junto al
  campo de teléfono, y el formulario conserva lo ya escrito.
- [x] CA3 · Dado un alta (con o sin correo) cuyo nombre y apellido coinciden con los de un paciente
  existente, activo o desactivado (sin distinguir mayúsculas, acentos ni espacios sobrantes), y
  además coincide el teléfono o la fecha de nacimiento, cuando se guarda, entonces todavía no se crea
  nada: se muestra un aviso «Ya existe un paciente con estos datos» con el nombre del existente, un
  enlace a su ficha y la opción «Registrar de todos modos»; al confirmarla se crea el paciente nuevo
  con los datos escritos, sin volver a escribirlos (salvo la contraseña temporal, que no se conserva:
  ver Decisiones). Sin coincidencia, el alta no muestra aviso.
- [x] CA4 · Dado un paciente sin correo, cuando el personal lo ve en el directorio o abre su ficha,
  entonces donde iría el correo aparece «Sin correo · sin acceso a la aplicación», y la búsqueda del
  directorio por nombre o teléfono lo encuentra.
- [x] CA5 · Dado un admin o dentista que edita a un paciente sin correo, cuando guarda sin añadir
  correo, entonces los cambios se guardan sin pedir correo; el teléfono sigue siendo obligatorio
  mientras no tenga correo (mismo mensaje que CA2). Dado un paciente con correo, al editarlo el
  correo sigue siendo obligatorio y dejarlo vacío se rechaza sin cambiar nada.
- [x] CA6 · Dado un admin o dentista que edita a un paciente sin correo, cuando añade un correo
  válido que no usa ninguna otra cuenta, entonces se guarda; desde ese momento el paciente puede
  crear su contraseña con «¿Olvidaste tu contraseña?» (spec 041) y entrar como cualquier paciente.
  No se le envía ningún correo automáticamente ni se muestra contraseña. Si el correo ya está
  registrado, se rechaza con «Este correo ya está registrado en el sistema.» sin cambiar nada.
- [x] CA7 (abuso) · Como alguien que conoce el nombre, el teléfono o la fecha de nacimiento de un
  paciente sin correo, intento entrar a su cuenta (con correo y contraseña, con Google o con la
  recuperación de contraseña) → no hay forma de acceder: el inicio de sesión responde como con
  cualquier credencial incorrecta y la recuperación responde como hoy sin enviar nada.
- [x] CA8 (abuso) · Como dentista, paciente o visitante sin sesión, envío directamente el alta de un
  paciente sin correo → se deniega como cualquier alta no permitida (solo admin y asistente dan de
  alta) y no se crea nada.
- [x] CA9 (abuso) · Como personal que registra a alguien con el nombre de otro paciente para
  averiguar sus datos, provoco el aviso de CA3 → el aviso muestra solo el nombre completo del
  existente y, si puedo ver su ficha, el enlace a ella; nunca su teléfono, su fecha de nacimiento,
  su correo ni datos clínicos.

## Fuera de alcance
- Que el dentista dé de alta pacientes: sigue siendo cosa de admin y asistente (spec 014).
- Quitar el correo a un paciente que ya lo tiene (le cortaría el acceso).
- Enviar al paciente una invitación o aviso al añadirle un correo; verificar que el correo es suyo.
- Fusionar fichas duplicadas ya existentes o detectar duplicados fuera del alta.
- El registro público de pacientes en la página de acceso: sigue exigiendo correo (spec 002).
- Recordatorios u otros avisos por teléfono, SMS o WhatsApp para pacientes sin correo.

## Seguridad y privacidad
- Datos sensibles involucrados: datos personales (nombre, teléfono, fecha de nacimiento, correo) y,
  a través de la ficha, de salud (`docs/security.md`, clasificación «confidencial»). La spec no crea
  datos nuevos: cambia cuándo el correo es obligatorio y quién tiene acceso a la aplicación.
- Quién puede hacer qué:
  - Admin y asistente → dar de alta pacientes con o sin correo; el asistente, con las reglas de
    alcance de la spec 014 cuando se implemente.
  - Admin y dentista → editar pacientes y añadir el correo a uno que no lo tenía (spec 006 CA4).
  - Dentista, paciente y visitante → no dan de alta pacientes (CA8).
  - Un paciente sin correo → no tiene acceso a la aplicación (CA7).
- Casos de abuso:
  - Como alguien que conoce datos de un paciente sin correo, intento entrar a su cuenta → no hay
    forma de acceder (CA7).
  - Como dentista o usuario sin permiso, envío el alta directamente → se deniega (CA8).
  - Como personal, uso el aviso de duplicado para sacar datos de otro paciente → solo ve nombre y
    enlace permitido (CA9).
  - Como personal, añado a un paciente sin correo un correo que ya usa otra cuenta para tomarla →
    se rechaza (CA6).

## Auditoría
Eventos que deben quedar registrados (`docs/constitution.md` P7, `docs/observability.md`), sin
nombre, teléfono ni correo en claro (spec 020 CA3):
- [x] CA10 · Dada un alta de paciente (con o sin correo), cuando se completa o se rechaza por
  permisos, entonces queda registrado quién, la acción «alta de paciente», el paciente creado, el
  resultado, si se registró con o sin acceso a la aplicación y si se confirmó pese al aviso de
  posible duplicado (CA3).
- [x] CA11 · Dado un admin o dentista que añade el correo a un paciente sin correo (CA6), cuando se
  guarda, entonces queda registrado como cambio de cuenta: quién, sobre qué paciente, el resultado y
  que el paciente pasó a tener acceso posible, sin el correo.

## Diseño
Quien registra en el mostrador debe poder dar de alta a alguien sin correo sin dudar ni inventar
datos, y entender en el momento si esa persona ya está registrada. Sistema:
[docs/design/system.md](../../design/system.md).

Pantallas que toca: alta de paciente (admin y asistente), edición de paciente (admin y dentista),
directorio y ficha del paciente, en móvil y escritorio. Qué debe lograr cada una:
- Alta: en cinco segundos se entiende que el correo es opcional y que sin él el teléfono es
  obligatorio y no habrá acceso a la aplicación.
- Aviso de posible duplicado (estado nuevo del alta): deja claro qué coincide y ofrece las dos
  salidas, abrir la ficha existente o registrar de todos modos.
- Directorio y ficha: «Sin correo · sin acceso a la aplicación» se distingue de un dato vacío.
- Edición: añadir el correo explica que el paciente podrá entrar creando su contraseña.

Qué debe diseñarse: la ayuda del campo de correo y del teléfono, el error de CA2, el aviso de CA3,
la confirmación de CA1 y la marca de CA4, con los componentes de campo, aviso y botón que ya
existen. Sin recursos de marca nuevos. Sin ciclo de candidatos de `/design-spec` (supuesto, ver
Decisiones): `/plan` usa los componentes del sistema y el usuario lo valida en pantalla durante
`/implement`. Acta: [design.md](design.md).

## Criterios de diseño
Modo: operar · sin ciclo de candidatos · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Campo «Correo» del alta | marcado como opcional en la etiqueta visible, con una ayuda que dice que sin correo el paciente no tendrá acceso y el teléfono es obligatorio | CA1, CA2 |
| K2 | Error del teléfono y aviso de duplicado | texto visible junto al campo o arriba del formulario, anunciado al lector de pantalla y no solo con color | CA2, CA3 |
| K3 | Aviso de duplicado | las dos acciones («Ver ficha», «Registrar de todos modos») visibles sin desplazar a 390 px, la principal distinguible | CA3 |
| K4 | Marca «Sin correo» en directorio y ficha | se lee completa a 320 px sin desbordar y no se confunde con un campo vacío | CA4 |
| K5 | Formularios a 320, 390 y 1440 px | sin desplazamiento lateral | CA1, CA5, CA6 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible,
toque ≥ 44 px en el móvil, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad WCAG 2.1 AA en las pantallas que toca (constitución P9 y sistema de diseño).
- El aviso de duplicado aparece en < 1 s en el 95 % de las altas con el volumen actual de pacientes.

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | contradicción | La petición pide que el dentista también dé de alta pacientes; la spec 014 (approved) no le da ese permiso | Se mantiene la 014: solo admin y asistente dan de alta; el dentista queda fuera de alcance (CA8) | usuario |
| 2026-11-20 | brecha | ¿Qué pasa si después se añade correo a un paciente sin correo? | Puede activar su acceso creando su contraseña con la recuperación de la 041; no se envía nada automáticamente (CA6) | usuario |
| 2026-11-20 | brecha | Sin correo, ¿cómo se evitan duplicados? | Avisar si coinciden nombre y apellido más teléfono o fecha de nacimiento, y dejar confirmar el alta (CA3) | usuario |
| 2026-11-20 | brecha | Dato mínimo de contacto sin correo | Teléfono obligatorio cuando no hay correo (CA2, CA5) | usuario |
| 2026-11-20 | implícita | Quién edita pacientes y, por tanto, puede añadir el correo | Admin y dentista; el asistente no edita | `docs/specs/006-pacientes/spec.md` CA4, `docs/specs/014-autorizacion-alcance-clinico/spec.md` |
| 2026-11-20 | implícita | El alta y el cambio de cuenta se auditan sin datos personales en claro | CA10 y CA11 | `docs/constitution.md` P7, `docs/specs/020-auditoria-base/spec.md` CA3 |
| 2026-11-20 | implícita | Recordatorio por correo (052) a un paciente sin correo | No se le envía: la 052 envía al correo de la cuenta. La 052 debe tolerar cuentas sin correo sin registrarlo como fallo | `docs/specs/052-recordatorio-correo/spec.md` CA1 (rama feature/052-recordatorio-correo) |
| 2026-11-20 | brecha | Coincidencia de nombres para el aviso de duplicado | Sin distinguir mayúsculas, acentos ni espacios sobrantes; incluye pacientes desactivados (confirmado el 2026-11-21, /analyze A15) | usuario |
| 2026-11-20 | brecha | Un correo con solo espacios | Cuenta como vacío (alta sin correo) (confirmado el 2026-11-21, /analyze A15) | usuario |
| 2026-11-21 | brecha | Con la spec 014, ¿el aviso de duplicado enseña el nombre de un paciente fuera del alcance del asistente? | Sí: el nombre se muestra siempre, nunca otros datos; el enlace solo si puede ver la ficha. La 014 lo acepta como excepción | usuario (/analyze A4) |
| 2026-11-21 | brecha | ¿Qué pasa con la contraseña temporal escrita al volver con el aviso de duplicado? | No se conserva: el campo vuelve vacío y, al confirmar con correo, se genera una y se muestra como hoy (CA3 «sin volver a escribirlos» no incluye la contraseña) | usuario (/analyze A2) |
| 2026-11-20 | supuesto | Diseño sin ciclo de candidatos, como la 051 | Componentes existentes; validación del usuario en pantalla | agente |
| 2026-11-20 | supuesto | Tamaño: 11 criterios (9 + 2 de auditoría) | Se mantiene en una spec: alta, aviso y correo posterior se liberan juntos o pierden sentido | agente |
| 2026-11-20 | diseño | Diseño de la spec | Sin ciclo de candidatos; aviso de posible duplicado como único componente nuevo (`.aviso-duplicado`) y textos de campos en el acta | [design.md](design.md) (`/design-spec 054`) |

## Notas para /plan
- Criterios de la 006 que cambia: CA2 (el correo deja de ser obligatorio en el alta; sin correo no se genera ni muestra contraseña) y CA4 (editar no exige correo a quien no lo tiene; se puede añadir).
- Hoy el alta crea siempre una fila en `usuarios` (rol cliente) con `email` NOT NULL y único, más su
  fila en `pacientes`; las citas y expedientes cuelgan de `pacientes`. Decidir si el paciente sin
  correo conserva su fila en `usuarios` (con correo nulo y sin contraseña utilizable) o no; la
  activación de CA6 vía recuperación (041) es más simple si la conserva.
- Validaciones actuales en `PacientesController@store` (`email` required|unique) y `@update`
  (`email` required); la búsqueda del directorio usa `usuarios.email` con `LIKE`.
- Revisar todo lo que lee `usuarios.email` de un cliente: inicio de sesión, vínculo con Google por
  correo (debe ignorar cuentas sin correo), recuperación (041), avisos (032), administración de
  usuarios (010) y el recordatorio de la 052 (avisar a su plan si se implementa después).
- La unicidad del correo debe seguir valiendo con varios pacientes sin correo.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-20 | Spec creada en draft | /specify |
| 2026-11-20 | Aprobada | decisión del usuario |
| 2026-11-21 | Decisiones: supuestos A15 confirmados; aviso y alcance de la 014 (A4); contraseña al volver con el aviso (A2). Vuelve a draft hasta aprobarla de nuevo | /analyze A2, A4, A15 |
| 2026-11-21 | CA3 aclara que la contraseña temporal no se conserva al volver con el aviso | /analyze A24 |
| 2026-11-21 | Aprobada de nuevo | decisión del usuario |
| 2026-11-21 | Implementada: CA1–CA11 con sus tests en verde (`PacienteSinCorreoTest`, `PosiblesDuplicadosTest`) | /implement |
| 2026-11-21 | Vuelve a approved: /review r1 changes_requested (R1–R4, R7, R15 → T030–T038) | /review |
| 2026-11-21 | Implementada de nuevo con T030–T038 | /implement |
