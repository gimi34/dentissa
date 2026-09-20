# Resultados de la comprobación de humo — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-execution (Operation) · **Alcance**: bugfix
**Fecha**: 2026-09-19
**Definición del humo**: `../deployment-pipeline/deployment-strategy.md` § «Comprobación de humo»
**Método**: puntos 1 a 4 conducidos en Chrome contra `http://localhost:8000` con
sesión de administrador; punto 5 ejecutado en el contenedor.

**Veredicto: 5 de 5 en verde. El despliegue se da por bueno.**

---

## Cómo se obtuvo la sesión

El humano inició sesión manualmente en la pestaña de Chrome y la verificación
continuó desde ahí. **En ningún momento se manejó la contraseña**: no se
introdujo en ningún formulario, no se registró en este artefacto y no aparece en
el registro de despliegue. Esa restricción no es discrecional, y la opción de
que el humano la facilitara —ofrecida por error al plantear la pregunta— se
retiró antes de que llegara a escribirla.

## Resultados

| # | Comprobación | Defecto que cubre | Resultado |
|---|---|---|---|
| 1 | `/admin/usuarios` muestra el listado real | TD-12, y el fallo de caché de vistas | **Verde** |
| 2 | «Agregar usuario» abre el formulario, no un 404 | TD-11 | **Verde** |
| 3 | Editar solo la duración conserva el precio | TD-8 | **Verde** |
| 4 | El selector de rol ofrece las cuatro opciones | TD-16 | **Verde** |
| 5 | La suite completa en el entorno desplegado | Todo lo anterior | **Verde** |

### Punto 1 — el listado real de usuarios

`/admin/usuarios` devuelve la pantalla «Control de Usuarios» con las tarjetas de
totales (1 Administrador, 1 Dentista, 2 Pacientes/Clientes), los filtros de
búsqueda y rol, y las fichas de usuario. **No es la pantalla de marcador de
posición** que devolvía el closure muerto de TD-12.

Dos detalles adicionales observados en la misma pantalla:

- El botón de cada ficha dice **«Desactivar»**, no «Eliminar»: la baja lógica de
  TD-16 está en la interfaz, no solo en el controlador.
- La hoja de estilos carga completa. Es la confirmación visual de que el paso 7
  del procedimiento corregido (`view:cache` antes de construir) hizo su trabajo.

### Punto 2 — el alta de usuarios

Pulsando «Agregar usuario» se llega a `/admin/usuarios/crear`, que devuelve el
formulario «Nuevo Usuario». **No un 404.** Es la corrección de TD-11: la ruta de
alta se registra antes que `/{id}` y `{id}` está restringido al formato UUID.

El formulario muestra el campo «Contraseña temporal» con la leyenda «Dejar vacío
para generar automáticamente» y la aclaración «Si se deja vacío se genera una
contraseña aleatoria» — la promesa que el formulario ya hacía y que FR3.6 ahora
cumple de verdad.

### Punto 3 — el precio sobrevive a una edición parcial

**Es la comprobación más importante del conjunto**, porque TD-8 es la pérdida
silenciosa: no da error, solo destruye el dato.

Procedimiento seguido, que reproduce exactamente el gesto que lo destruía:

1. Abrir la edición de «Colocación de brackets». El formulario trae precargados
   el precio `18000.00` y el color `#ed93b1` — ya de por sí media corrección,
   porque antes el formulario no llevaba esos campos.
2. Cambiar **solo la duración**, de 90 a 95 minutos. Guardar.
3. Consultar la base directamente.

Resultado:

```
      nombre: Colocación de brackets
duracion_min: 95
      precio: 18000.00
color_agenda: #ed93b1
```

Y la propagación a las vistas SQL, que es la consecuencia que el enunciado de la
intención nombra explícitamente:

```
servicio                  precio
Colocación de brackets    18000.00
```

**Contra el código con el defecto, esto habría devuelto `precio: 0.00` y
`color_agenda: #F4C0D1`.**

**Los datos se dejaron como estaban**: la duración se restauró a 90 por el mismo
camino, y se verificó (`duracion_min: 90`, `precio: 18000.00`,
`color_agenda: #ed93b1`).

### Punto 4 — el selector de rol

Abriendo la edición de un usuario con rol `dentista` —no de un cliente, cuyo
selector está oculto por diseño— el desplegable devuelve:

```
combobox "Dentista"
 option "Administrador"        value="admin"
 option "Dentista" (selected)  value="dentista"
 option "Asistente"            value="asistente"
 option "Paciente / Cliente"   value="cliente"
```

Las cuatro opciones del enum, **y la actual preseleccionada**. Esa preselección
es la mitad de TD-16 que más importa: sin ella el navegador enviaba la primera
opción del desplegable al guardar, de modo que editar un asistente lo convertía
en administrador. La URL de la página (`/admin/usuarios/<uuid>/editar`) confirma
de paso que la restricción de ruta sobre `{id}` está activa.

### Punto 5 — la suite completa en el entorno desplegado

```
OK (22 tests, 108 assertions)
```

22 de 22, cero fallos, ejecutada dentro del contenedor contra la base de pruebas
`dentissa_test`.

## Verificación adicional no prevista en el humo

El filtro del listado se verificó de paso, al necesitar un usuario no-cliente
para el punto 4: `/admin/usuarios?tipo=dentista` devuelve únicamente al dentista,
bajo el encabezado «Dentistas 1», con el desplegable de rol mostrando
«Dentistas». Es el punto 18 de la lista manual, cubierto sin coste adicional.

## Lo que el humo NO cubre

Decirlo importa tanto como lo verificado:

- **El recorrido completo de la lista manual de 19 puntos.** El humo toca 1, 2,
  3, 4, 6 y 18. Los demás tienen contrapartida automática en la suite, pero el
  recorrido manual sigue siendo lo que verifica apariencia e interacción real.
- **El comportamiento de los modales.** Que el modal de desactivación ya no
  prometa un borrado irreversible está fijado por prueba automática, pero no se
  abrió en el navegador.
- **El alta y la baja de usuarios de extremo a extremo.** No se creó ni desactivó
  ningún usuario durante el humo, deliberadamente: son escrituras sobre datos
  reales que nadie pidió. Ambas están cubiertas por regresión automática.
