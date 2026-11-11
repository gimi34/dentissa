---
lane: fix
status: done                 # open → done → released · escalated (→ escalated_to)
base: 2590a3e                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [038]               # specs cuyo comportamiento toca ([] si ninguna)
covers: [K8]                 # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: la página de inicio salta al cargar (CLS 0,212)

## Problema
La spec 038 pide una estabilidad visual (CLS) de **≤ 0,1** en la página de inicio (K8, modo persuadir).
La página real da **0,212** a 1440 px: lo midió `design/medir.mjs` de la 039 sobre `/` renderizada por
la app (K11 de la 039, el mismo criterio) en su `/implement` y en su `/review` r4. Se aceptó en su plan
como D14, a la espera de este `/fix`. El candidato de diseño de la 038 daba 0,012.

Al cargar, el titular, la arcada y los arcos del fondo bajan de golpe entre 15 y 25 px.

## Reproducción
1. Con las seis fotos de prueba publicadas (`.ai/inspect/039-fotos.php publicar`), se renderiza `/` con
   `.ai/inspect/039-render.php inicio` y se mide con
   `docs/specs/039-galeria-trabajos/design/medir.mjs .ai/inspect/039-build --solo inicio` (Playwright 1.63.0).
   Resultado: «❌ K11 Estabilidad visual: 0.212 (objetivo 0.1)», con todas las puertas en verde.
2. Un `PerformanceObserver` de `layout-shift` a 1440 px da dos saltos: 0,191 a los 188 ms (la arcada baja
   25 px y los arcos del fondo, 15 px) y 0,013 a los 257 ms (el cambio de fuente). A 390 px, 0,073, con el
   titular 22,5 px más abajo.
3. La misma página con la pastilla ya escrita en el HTML da 0,019 a 1440 px y 0,045 a 390 px: la pastilla
   explica todo el salto que pasa del objetivo.

Test que prueba el arreglo: `PaginasPublicasCssTest::test_la_pastilla_reserva_su_fila_antes_del_script`.
Antes del arreglo: «Con JavaScript, la pastilla ocupa su sitio antes de escribirse» (no hay regla que la
reserve). El CLS no se puede medir en PHPUnit; la medida de verdad es `medir.mjs` (K11), en «Verificación».

## Causa raíz
La pastilla «¿Está abierto ahora?» (`.pub-status`) se oculta con `display: none` mientras no tiene
`data-open`, para que sin JavaScript no aparezca (plan de la 038). `publico.js` va con `defer` y la escribe
después del primer pintado. Como la pastilla ocupa una fila propia de la lista del hero, al aparecer la
lista crece entre 50 y 57 px. El hero centra sus columnas, así que la arcada baja la mitad, y los arcos del
fondo, que miden un 70 % del alto del hero, bajan también. No se comprobó por qué el candidato de diseño
daba 0,012; lo más probable es que allí el script corriera antes del primer pintado.

## Cambio
`public/css/publico.css`: dentro de `@media (scripting: enabled)`, la pastilla sin `data-open` pasa a
`display: inline-flex; visibility: hidden` y, mientras está vacía, su `<li>` toma `flex-basis: 100%`. Así
su fila está reservada desde el primer pintado y, al escribirla, el hero no se mueve; escrita, la lista se
coloca como siempre. Sin JavaScript la regla no aplica y la página queda
como estaba. Un navegador sin soporte de `scripting` se queda con el comportamiento de antes (salta, pero
se ve bien). `PaginasPublicasCssTest::test_los_cortes_son_900_y_560` admite la nueva condición, que no es un
corte de ancho (como `prefers-reduced-motion`).

## Escalado
`lane-check` al empezar (CSS y test previstos) y sobre el diff real desde `2590a3e` (`public/css/publico.css`,
`tests/Feature/PaginasPublicasCssTest.php` y esta ficha): «Escalar: no. El cambio cabe en el carril.» No
cambia tokens ni el aspecto del componente `.pub-status` de `system.md`: solo cuándo ocupa su sitio.

## Verificación
- `medir.mjs` de la 039 sobre `/` renderizada por la app, con las seis fotos de prueba (`--solo inicio`):
  - base `2590a3e`: puertas 12/12 · KPIs 4/5 · «❌ K11 Estabilidad visual: 0.212»;
  - con el arreglo: puertas 12/12 · KPIs 5/5 · **K11 0,018**; la variante sin mapa, 12/12 y 2/2.
- CLS (`PerformanceObserver`, carga de 2 s), base → arreglo: 320 px 0,026 con el arreglo; 390 px 0,073 → 0,045;
  560 y 700 px 0,002; 800 px 0,021; 899 px 0,017; 900 px 0,082 → 0,045; 1100 px 0,033 → 0,001; 1200 px
  0,031 → 0,001; 1440 px 0,204 → 0,013. Entre 960 y 1024 px, 0,24 → 0,19: es otra causa (ver «Decisiones»).
- Lista del hero en 11 anchos (320–1440 px) con JavaScript: posiciones finales idénticas a la base. Sin
  JavaScript: idénticas a la base (sin pastilla y sin hueco).
- Tests: `test_la_pastilla_reserva_su_fila_antes_del_script` falla sin el arreglo y pasa con él; los de las
  páginas públicas (`PaginasPublicasCssTest`, `PaginaInicioTest`, `PaginasPublicasTest`), 53 en verde.
  Pint sobre el test: PASS. Suite completa: 913 pasan (14 006 aserciones), los 912 de `main` más el nuevo.
- Review ligera (subagente `standard`): sin bloqueantes. Con el CSS revertido el test falla y, restaurado,
  pasa (hash idéntico). La causa explica el síntoma y el arreglo ataca la causa. Menores:
  - (2) `flex-basis: 100%` también regía con la pastilla escrita y, de 750 a 899 px, bajaba el horario a la
    segunda fila: **corregido**, la fila entera solo mientras está vacía, y un test lo comprueba. Medido
    después en los 11 anchos.
  - (4) «Verificación» pendiente: resuelto aquí.
  - (1) y (3): aceptados, ver «Decisiones».

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-11 | implícita | K8 de la 038 (CLS ≤ 0,1) se cumplía en el candidato y no en la página real | Es un fix: lo define la spec y no hay nada que decidir sobre cómo debe ser | spec 038, K8; plan de la 039, D14 |
| 2026-11-11 | supuesto | ¿Pintar la pastilla en el servidor, reservar su sitio o moverla? | Reservarla solo con JavaScript (`@media (scripting: enabled)`): pintarla en el servidor la mostraría sin JavaScript, contra el plan de la 038 («sin JavaScript no aparece»), y moverla cambiaría el diseño | — |
| 2026-11-11 | supuesto | Review (1): si `publico.js` no carga, falla antes de escribirla (`montarCarrusel` va primero) o `estadoAhora` no da texto, queda una fila vacía de 44 px con JavaScript activo | Se acepta: es raro, y lo que queda es un hueco, no contenido roto | — |
| 2026-11-11 | supuesto | Review (3): otros cambios tras el primer pintado (`.js .pub-consulta.is-live`, `[data-today]`, el aviso de la 044 que se retira) | Fuera de la vista al cargar, o fuera de la medida de K8; no se tocan | — |
| 2026-11-11 | supuesto | Entre 960 y 1024 px queda un salto de ~0,19 al cargar, ya presente en la base (0,24): la arcada baja 33 px a los ~400 ms, a la vez que el cambio de fuente; precargar las fuentes no lo arregla | Otra causa: ficha `/fix` aparte, a continuación. K11 se mide al ancho mayor y ya pasa | usuario |
