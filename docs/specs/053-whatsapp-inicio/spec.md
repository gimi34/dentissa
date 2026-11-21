---
id: 053
slug: whatsapp-inicio
status: approved
created: 2026-11-20
extends: [038, 045]
---

# 053 · Escríbenos por WhatsApp desde la página de inicio

## Problema
En México, WhatsApp es el canal con el que la gente prefiere hablar con una clínica dental
(`docs/design/nicho.md`), pero la página de inicio solo ofrece llamar en horario de trabajo o crear
una cuenta para agendar. Quien tiene una duda rápida («¿atienden a niños?», «¿tienen hueco el
sábado?») y no quiere llamar ni registrarse, se va sin escribir. Esta spec añade a la página de
inicio un botón que abre un chat de WhatsApp con la clínica, con un mensaje ya escrito. Es un enlace
directo: la aplicación no envía ni recibe mensajes de WhatsApp.

## Historias de usuario
- Como visitante de la página de inicio, quiero escribir a la clínica por WhatsApp con un toque,
  para resolver una duda o pedir información sin llamar ni crear una cuenta.
- Como visitante que escribe por WhatsApp, quiero saber qué hace la clínica con mi número y mi
  mensaje, para decidir si escribo.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante, con o sin sesión, en la página de inicio, cuando mira la primera
  pantalla, entonces ve «Escríbenos por WhatsApp» junto al horario y el teléfono (amplía 038 CA1).
- [ ] CA2 · Dado un visitante en la sección de contacto de la página de inicio, cuando la mira,
  entonces ve «Escríbenos por WhatsApp» junto al teléfono; el resto del contacto de 038 CA5 no
  cambia (amplía 038 CA5).
- [ ] CA3 · Dado un visitante que pulsa cualquiera de los dos botones, cuando se abre el enlace,
  entonces se abre WhatsApp (la aplicación en el móvil o WhatsApp Web en un ordenador) con un chat al
  mismo número que el teléfono de la página, 753 000 0000 con la clave de México, y el mensaje ya
  escrito «Hola, quiero información para agendar una cita.», que el visitante puede cambiar antes de
  enviarlo; en un ordenador, la página de inicio sigue abierta en su pestaña.
- [ ] CA4 · Dado un navegador sin JavaScript, cuando el visitante pulsa cualquiera de los dos
  botones, entonces funciona igual que en CA3.
- [ ] CA5 · (abuso) Como alguien que inspecciona el enlace con una sesión abierta, busco datos de la
  cuenta o de la página → el mensaje y el enlace son los mismos para todos los visitantes y no
  llevan nombre, correo, teléfono ni ningún otro dato de la sesión o de la visita.
- [ ] CA6 · Dado un visitante que lee el aviso de privacidad, cuando llega al apartado de servicios
  de terceros, entonces lee que, si escribe por WhatsApp, la clínica recibe su número y lo que
  escriba solo para responderle, y que WhatsApp es un servicio de un tercero con su propio aviso de
  privacidad (amplía 045).

## Fuera de alcance
- Recordatorios u otros mensajes automáticos por WhatsApp, y recibir o contestar mensajes desde la
  aplicación (decisión del usuario, 2026-11-20). Los recordatorios van por correo (spec 052).
- Un botón flotante fijo en todas las páginas públicas, y el botón en el pie, `/servicios`, la
  galería o el panel (decisión del usuario, 2026-11-20).
- Un número real: el del proyecto es ficticio, así que el chat no llega a nadie (decisión del
  usuario, 2026-11-20; `docs/constitution.md` Restricciones).
- Escribir por correo desde la página de inicio.
- Elegir el mensaje según el servicio o la sección, o editar el número o el mensaje desde la
  administración: se cambian en el código, como el resto del contacto (038).
- Agendar citas por WhatsApp: se siguen agendando con cuenta.

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno lo envía la aplicación. Si el visitante decide escribir, su
  número y su mensaje salen de su teléfono a WhatsApp y a la clínica, fuera de la aplicación; el
  aviso de privacidad lo explica (CA6).
- Quién puede hacer qué: cualquier visitante, con o sin sesión, ve y usa el botón; ningún rol gana
  permisos nuevos.
- Casos de abuso:
  - Como alguien que inspecciona el enlace con una sesión abierta, busco datos de la cuenta → el
    enlace es igual para todos y no lleva datos (CA5).
  - Como alguien que busca rastrear a los visitantes, espero que la página cargue algo de WhatsApp o
    de Meta al abrirse → no carga nada de terceros nuevo; solo se contacta con WhatsApp cuando el
    visitante pulsa el botón (requisitos no funcionales).

## Diseño
El botón tiene que sentirse como una forma más, cercana, de hablar con la clínica, al lado del
teléfono y sin competir con «Agendar mi primera cita», que sigue siendo la acción principal (038).
Sistema: [docs/design/system.md](../../design/system.md). Acta: [design.md](design.md) (`c3`, 81/100).

- Qué debe lograr: en cinco segundos, quien llega entiende que puede escribir por WhatsApp sin
  registrarse.
- Recursos de marca existentes: la página de inicio de la 038 y el sistema de diseño. El símbolo de
  WhatsApp, si se usa, sigue las normas de uso de su marca y se sirve desde el propio sitio.
- Qué debe diseñarse: el botón en la primera pantalla y en la sección de contacto, a 320, 390, 900 y
  1440 px, con sus estados de foco y pulsado.

## Criterios de diseño
Modo: persuadir · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | «Escríbenos por WhatsApp» en la primera pantalla | visible entero a 320, 390, 900 y 1440 px | CA1 |
| K2 | «Agendar mi primera cita» sigue siendo la acción principal | entero en la primera pantalla a los cuatro anchos; que destaque más que el botón de WhatsApp lo juzga el punto `jerarquia` de la rúbrica | 038 CA1 |
| K3 | «Escríbenos por WhatsApp» en la sección de contacto | junto al teléfono, a 320, 390, 900 y 1440 px (sin tipo en `evaluate.mjs`: se comprueba en el DOM del candidato y con test en `/implement`) | CA2 |
| K4 | Contacto de la 038 | los textos de 038 K5 siguen presentes | CA2 |
| K5 | Tamaño de toque de los dos botones | ≥ 44 × 44 px (puerta `G-toque`) | P9 (DS6) |
| K6 | JavaScript de la ruta | sin aumento: ≤ 13 994 bytes, lo que pesa hoy `publico.js`, medido con el mapa en blanco | CA4, P10 |
| K7 | Titular y teléfono en la primera pantalla | los dos visibles a 900 y 1440 px (038 K2) | 038 CA1 |
| K8 | Estabilidad visual (CLS) | ≤ 0.1 | modo persuadir |
| K9 | Peso de la primera visita | ≤ 1 MB con el mapa en blanco (RNF de la 038) | P10 |

Puertas: las de `design-spec/references/criterios.md` §2; `G-red` sigue admitiendo solo
`www.google.com` (el mapa de la 038); `G-sin-js` cubre CA4. Rúbrica: pesos por defecto.

## Requisitos no funcionales
- Accesibilidad (P9, WCAG 2.1 AA): los dos botones se anuncian como «Escríbenos por WhatsApp» y
  avisan de que abren WhatsApp; contraste AA y foco visible.
- Privacidad y peso: la página de inicio no carga ningún script, imagen ni tipo de letra de WhatsApp
  o de Meta; solo se contacta con WhatsApp cuando el visitante pulsa.
- Descubribilidad (P10): `/` conserva su título, su descripción, su canónica y su único `h1`, y sigue
  indexable (`docs/seo.md`).

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | brecha | ¿Qué hace WhatsApp en el sistema? | Solo un enlace «Escríbenos» de mensaje directo en la página de inicio; nada automático | usuario |
| 2026-11-20 | brecha | ¿Qué número abre el botón? | El ficticio 753 000 0000, el mismo del teléfono de la página | usuario · `docs/constitution.md` Restricciones |
| 2026-11-20 | brecha | ¿Dónde aparece? | En la primera pantalla y en la sección de contacto, junto al teléfono (CA1, CA2) | usuario |
| 2026-11-20 | implícita | Que funcione sin JavaScript | Sí (CA4): la página de inicio está completa sin él | `docs/specs/038-pagina-inicio/spec.md` · `AGENTS.md` (layout público) |
| 2026-11-20 | supuesto | Mensaje ya escrito | «Hola, quiero información para agendar una cita.» (CA3) | /specify |
| 2026-11-20 | supuesto | ¿El aviso de privacidad tiene que nombrar WhatsApp? | Sí, en el apartado de terceros, como ya nombra el mapa de Google (CA6) | /specify · `docs/specs/045-terminos-privacidad/spec.md` |
| 2026-11-20 | supuesto | ¿Pestaña nueva en un ordenador? | Sí, para que la página de inicio siga abierta (CA3) | /specify |
| 2026-11-20 | diseño | Diseño del botón en la primera pantalla y en contacto | `c3` aceptado con 81/100 (c2 con el logotipo de WhatsApp de una tinta, decisión del usuario): teléfono y WhatsApp en un mismo grupo; en contacto, WhatsApp y «Cómo llegar» se reparten el ancho; sin cambios al sistema. K6 y K9 medidos sin el mapa; K2 y K3 ajustados en la tabla | [design.md](design.md) |

## Notas para /plan
- El contacto sale de `$contacto` (`telefono`, `telefono_enlace`) en `resources/views/publico/inicio.blade.php:43`
  y `:194`; el número de WhatsApp debería salir de la misma fuente.
- Enlace previsto: `https://wa.me/527530000000?text=…` con `target="_blank"` y `rel="noopener noreferrer"`.
- El aviso de privacidad: `resources/views/publico/legal/privacidad-texto.blade.php:56` (párrafo del
  mapa de Google).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-20 | `draft` → `approved` | aprobada por el usuario |
