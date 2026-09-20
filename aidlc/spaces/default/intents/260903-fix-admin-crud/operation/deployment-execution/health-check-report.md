# Informe de salud posterior al despliegue — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-execution (Operation) · **Alcance**: bugfix
**Fecha**: 2026-09-19
**Veredicto: sano.** Con una acción de seguimiento abierta sobre los datos, que
no es una regresión de este despliegue.

---

## Estado de los servicios

| Servicio | Estado | Tiempo en marcha |
|---|---|---|
| `app` (PHP-FPM 8.4.23) | `running` | 50 minutos, sin reinicios |
| `nginx` (alpine) | `running` | 50 minutos, sin reinicios |
| `db` (MariaDB 10.11.18) | `running` | 50 minutos, sin reinicios |
| `phpmyadmin` | `running` | 50 minutos, sin reinicios |

Ningún servicio se reinició durante el despliegue, porque el código va montado
desde el host. **Tiempo de inactividad: cero.**

## Respuesta de la aplicación

| Ruta | Código | Tiempo | Esperado |
|---|---|---|---|
| `/login` | 200 | 0,070 s | Sí |
| `/` | 302 | 0,105 s | Sí — la raíz siempre redirige según el rol, por diseño |
| `/admin/usuarios` | 302 | 0,066 s | Sí — redirige a login sin sesión; con sesión devuelve 200, verificado en el humo |
| `/admin/servicios` | 302 | 0,058 s | Sí — igual que la anterior |

Todas por debajo de 110 ms. No hay objetivo de latencia definido para este
proyecto, así que estos valores son línea base, no comparación contra umbral.

## Conectividad de base de datos

```
ok    10.11.18-MariaDB-ubu2204
```

Conexión correcta. Las 6 migraciones de Laravel figuran como `Ran`; no hay
ninguna pendiente.

## Registro de errores

Cero líneas `ERROR` o `CRITICAL` en la cola de `storage/logs/laravel.log`
después del despliegue.

## Integridad de los datos tras el humo

El punto 3 del humo escribió deliberadamente sobre un servicio real y después lo
restauró. Estado final verificado:

```
      nombre: Colocación de brackets
duracion_min: 90          <- restaurado a su valor original
      precio: 18000.00    <- intacto
color_agenda: #ed93b1     <- intacto
```

Total de servicios: 15, sin cambios en el recuento.

---

## Daño preexistente en los datos

**Este es el hallazgo del informe, y no es una regresión del despliegue.**

Al verificar la integridad tras el humo, una consulta de control reveló que
**dos de los quince servicios tienen precio 0,00**. Comparados con el volcado
original `database/dentissa.sql`, que es el estado sembrado:

| Servicio | Precio en el volcado | Precio actual | Diferencia |
|---|---|---|---|
| Consulta general | **400,00** | **0,00** | −400,00 |
| Blanqueamiento dental | **2.800,00** | **0,00** | −2.800,00 |

Los otros trece coinciden exactamente con el volcado.

**Es la huella de TD-8.** Alguien editó esos dos servicios desde el panel antes
de que se corrigiera el defecto, y `ServiciosController::update()` les puso el
precio a cero en silencio. Un indicio adicional lo confirma: el registro actual
se llama «blanqueamiento dental» en minúsculas, mientras que el volcado dice
«Blanqueamiento dental» — ese registro pasó por el formulario.

### Esto refuta el supuesto A2 de los requisitos

`requirements.md` § 5 recoge:

> **A2** — Los 15 servicios del volcado con precios de 200,00 a 18.000,00 son
> datos válidos de producción y deben preservarse. **No se ha ejecutado ninguna
> edición destructiva sobre ellos que haya que revertir.**

La segunda frase es falsa. Se han ejecutado dos, y hay algo que revertir. El
supuesto se formuló en Inception sin comprobarlo contra la base, y nadie lo
verificó hasta ahora.

### Qué hace y qué no hace esta corrección

La corrección **detiene la pérdida futura**: a partir de ahora, editar un
servicio conserva su precio, y eso está fijado por regresión automática. Lo que
**no** hace es sanar lo ya perdido. Ninguna corrección de código puede: los
valores originales solo existen en el volcado.

### Reparación propuesta

Requiere tu decisión. **No la he ejecutado**: es una escritura sobre datos
reales que nadie ha pedido y queda fuera del alcance de esta intención.

```sql
-- Restaurar los dos precios perdidos a su valor del volcado original.
-- Revisa los importes antes de ejecutar: si los precios de la clínica han
-- cambiado desde que se sembró la base, el valor correcto puede no ser este.
UPDATE servicios SET precio = 400.00  WHERE nombre = 'Consulta general'      AND precio = 0;
UPDATE servicios SET precio = 2800.00 WHERE nombre = 'blanqueamiento dental' AND precio = 0;
```

Con un respaldo previo, como siempre:

```bash
docker compose exec -T db mariadb-dump -uroot -proot --databases dentissa \
  --single-transaction --routines --triggers > respaldo-$(date +%Y%m%d-%H%M%S).sql
```

La condición `AND precio = 0` hace la orden idempotente y la impide sobreescribir
un precio que alguien haya corregido a mano entretanto.

**Antes de ejecutarla, confirma los importes.** El volcado refleja el precio
cuando se sembró la base; si la clínica ha subido tarifas desde entonces, 400,00
y 2.800,00 son el valor histórico, no el vigente. Restaurar un precio incorrecto
es el mismo tipo de daño que se está reparando.

### Por qué conviene mirar si hay más

Estos dos se detectaron porque el precio quedó en cero, que es visible. TD-8
también forzaba `color_agenda` al valor por defecto `#F4C0D1`, y eso **no** deja
rastro obvio. Merece una comprobación:

```sql
SELECT nombre, color_agenda FROM servicios WHERE color_agenda = '#F4C0D1';
```

Cualquier resultado es probablemente un servicio que también pasó por el
formulario defectuoso y perdió su color.

---

## Riesgos conocidos, no resueltos aquí

Ninguno lo introduce este despliegue; todos preexisten y están documentados en
`../deployment-pipeline/deployment-strategy.md` § «Riesgos que este despliegue no
resuelve».

- Credenciales en claro en `docker-compose.yml` y puertos 3306 y 8080
  publicados al host.
- Sin observabilidad: no hay métrica de salud ni de errores, así que un fallo en
  ejecución se detecta cuando alguien lo ve.
- Sin copias de seguridad automáticas: el respaldo de este despliegue fue manual.
- El esquema del dominio vive en un volcado, no en migraciones.

## Estado final

**El despliegue es sano y no requiere vuelta atrás.** Los cuatro defectos de la
intención están corregidos y verificados en el entorno real. Queda abierta una
decisión sobre dos precios que se perdieron antes de esta corrección.
