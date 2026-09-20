# Runbook de vuelta atrás — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-pipeline (Operation) · **Alcance**: bugfix
**Fecha**: 2026-09-19
**Alcance del runbook**: código **y** datos (respuesta Q4 = B)

---

## Cuándo usar esto

Cuando cualquiera de los disparadores de `deployment-strategy.md` § «Vuelta
atrás» se cumple. En una frase: **cuando la comprobación de humo falla y no es
un problema de caché.**

## Antes de nada: descarta el falso positivo más común

El modo de fallo más probable de este despliegue **no requiere vuelta atrás**.

Si el listado de usuarios lanza un error que menciona `admin.usuarios.destroy`,
o «Route [admin.usuarios.destroy] not defined», el código está bien y lo que
falta es limpiar las vistas compiladas:

```bash
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan config:clear
```

Vuelve a probar antes de revertir nada. Revertir por esto sería deshacer una
corrección correcta por no haber ejecutado un paso del despliegue.

## Antes de desplegar: el respaldo previo

Esto no es parte de la vuelta atrás, es lo que la hace posible. Se ejecuta en el
paso 2 de `cd-config.md`, **antes** del `git pull`.

### Respaldo de la base de datos

```bash
docker compose exec -T db mariadb-dump -uroot -proot --databases dentissa --single-transaction --routines --triggers > respaldo-$(date +%Y%m%d-%H%M%S).sql
```

**Esta orden está verificada**, no supuesta: se ejecutó el 2026-09-19 y produjo
39 kB con 18 tablas y las 2 vistas. Dos detalles que cuestan una sesión si se
descubren en caliente:

- **`-proot` es obligatorio.** Sin la contraseña, MariaDB responde
  `Access denied for user 'root'@'localhost' (using password: NO)`. Las
  credenciales las fija `docker-compose.yml` (`MYSQL_ROOT_PASSWORD: root`); el
  `MYSQL_ALLOW_EMPTY_PASSWORD` que también declara **no** aplica a la conexión
  por socket dentro del contenedor.
- **`--databases dentissa`, nunca `--all-databases`.** El volcado completo
  arrastra las tablas de sistema de MariaDB (233 tablas, 5 MB), y restaurarlo
  sobreescribiría la tabla de usuarios del motor. Es innecesario y peligroso.
  `dentissa_test` no se respalda a propósito: se recrea con
  `bash database/testing/bootstrap-test-db.sh`.

Por qué las demás banderas:

- `--single-transaction` toma una instantánea consistente sin bloquear las
  tablas InnoDB.
- `--routines --triggers` conserva las vistas SQL `v_citas_hoy` y
  `v_historial_paciente`, de las que depende el sistema y que un volcado
  descuidado deja fuera.
- `-T` en `docker compose exec` evita que Docker asigne un pseudo-terminal, que
  corrompería el archivo con caracteres de control.

**Comprueba que el respaldo sirve** antes de seguir. Un respaldo sin verificar
no es un respaldo:

```bash
ls -lh respaldo-*.sql                                # verificado: 39 kB
grep -c "CREATE TABLE" respaldo-*.sql                # verificado: 18
grep -c "CREATE VIEW" respaldo-*.sql                 # verificado: 2 (v_citas_hoy, v_historial_paciente)
grep -c "INSERT INTO .servicios." respaldo-*.sql     # verificado: 1 (los 15 servicios en un INSERT)
```

**Sin comillas invertidas en los patrones.** Los nombres de tabla van
entrecomillados con acento grave en el volcado, pero incluirlos en el patrón
hace que el shell intente ejecutar lo que va entre ellas y el recuento salga 0
sobre un respaldo perfectamente válido. Usa `.` como comodín, como arriba. Este
error se cometió al ejecutar el despliegue del 2026-09-19 y dio un falso
negativo de «0 vistas» sobre un archivo que sí las tenía.

### Anotar el punto de retorno

```bash
git rev-parse HEAD > .ultimo-despliegue.txt
cat .ultimo-despliegue.txt
```

Guarda ese identificador donde no dependa del árbol de trabajo: si acabas
haciendo `git reset --hard`, un archivo sin seguimiento sobrevive, pero anotarlo
también fuera del repositorio cuesta cero.

---

## Vuelta atrás — solo código

Es el caso habitual. Úsalo cuando el despliegue falló pero **nadie ha creado ni
editado nada** desde las pantallas corregidas.

**1. Confirmar qué hay desplegado ahora.**

```bash
git log --oneline -3
cat .ultimo-despliegue.txt
```

**2. Volver al commit anterior al despliegue.**

```bash
git reset --hard "$(cat .ultimo-despliegue.txt)"
```

Si prefieres no reescribir la posición de la rama —por ejemplo porque otras
personas comparten el repositorio— usa la alternativa no destructiva, que crea
un commit nuevo que deshace los cambios:

```bash
git revert --no-edit "$(cat .ultimo-despliegue.txt)"..HEAD
```

**3. Reconstruir los artefactos de front-end al estado anterior.**

```bash
npm run build
```

Necesario porque `public/build/` contiene los artefactos de la versión que se
está revirtiendo, y los nombres de archivo llevan huella de contenido.

**4. Limpiar las cachés. Igual de obligatorio que al desplegar.**

```bash
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan config:clear
```

Al revertir, la ruta `admin.usuarios.destroy` **vuelve a existir**. Las vistas
compiladas de la versión corregida no la referencian, así que el desajuste es
simétrico: sin limpiar la caché, la vuelta atrás falla igual que fallaba el
despliegue.

**5. Verificar.**

```bash
docker compose ps
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/login
```

Los cuatro servicios en `running` y un `200`. Después, abrir `/admin/usuarios` y
confirmar que la aplicación responde — volverá a mostrar el comportamiento
defectuoso original, que es exactamente lo que se pidió al revertir.

---

## Vuelta atrás — código y datos

Úsalo cuando **sí** se han creado o editado registros desde las pantallas
corregidas y no quieres conservarlos.

> **Para antes de ejecutarlo.** Restaurar el respaldo **borra todo lo escrito
> desde que se tomó**, no solo lo que salió mal. Si entre el despliegue y ahora
> alguien registró un paciente o una cita legítimos, los perderás. Si no estás
> seguro, haz primero un segundo volcado del estado actual —para poder
> rescatar filas después— y solo entonces restaura.

**1. Volcar el estado actual antes de destruirlo.**

```bash
docker compose exec -T db mariadb-dump -uroot -proot --databases dentissa --single-transaction --routines --triggers > antes-de-restaurar-$(date +%Y%m%d-%H%M%S).sql
```

**2. Revertir el código** siguiendo los pasos 1 a 4 de la sección anterior.

**3. Restaurar la base desde el respaldo previo.**

```bash
docker compose exec -T db mariadb -uroot -proot < respaldo-AAAAMMDD-HHMMSS.sql
```

Sustituye el nombre por el del respaldo real. El archivo entra por la entrada
estándar del contenedor; `-T` sigue siendo necesario, y `-proot` también. El
volcado lleva su propio `CREATE DATABASE`/`USE dentissa`, así que no hace falta
nombrar la base en la orden.

**4. Comprobar que los datos volvieron.**

```bash
docker compose exec -T db mariadb -uroot -proot -e "USE dentissa; SELECT COUNT(*) AS usuarios FROM usuarios; SELECT COUNT(*) AS servicios FROM servicios; SELECT nombre, precio FROM servicios ORDER BY precio DESC LIMIT 3;"
```

Los 15 servicios con precios de 200,00 a 18.000,00 deben estar ahí, con
«Colocación de brackets» a 18.000,00 en cabeza. Son los datos que el supuesto
**A2** de los requisitos identifica como válidos de producción.

**5. Verificar la aplicación** como en el paso 5 de la sección anterior.

---

## Qué NO hace falta revertir

Vale la pena decirlo para que nadie pierda tiempo buscándolo:

- **Migraciones de base de datos**: no hay. Esta corrección no toca el esquema
  (restricción C1).
- **Imágenes Docker**: no se reconstruyen para desplegar esta corrección, porque
  el código va montado. Nada que revertir.
- **Variables de entorno**: esta corrección no introduce ninguna. `.env` no se
  toca.
- **La base de pruebas `dentissa_test`**: es recreable en cualquier momento con
  `bash database/testing/bootstrap-test-db.sh`. No merece tratamiento especial.

## Después de revertir

**1. Anotar qué pasó.** Sin ceremonia: una línea en el registro de la intención
con la fecha, qué punto del humo falló y qué se revirtió.

**2. No volver a desplegar lo mismo sin entender el fallo.** Una vuelta atrás
que se deshace repitiendo el mismo despliegue no es una vuelta atrás, es un
aplazamiento.

**3. Reproducir el fallo en la máquina de desarrollo.** Si la suite pasa allí
(`OK (22 tests, 108 assertions)`) pero el humo falló en destino, la diferencia
está en el entorno, no en el código: caché, permisos, o el volcado de base de
datos desincronizado.

## Escalado y contacto

Con destino local y una sola persona operando (respuestas Q1 = A y Q3 = A), no
hay cadena de escalado que documentar: quien despliega es quien revierte.

Si esto llega a tener un servidor compartido, esta sección tiene que crecer con
al menos un nombre, una vía de contacto y una ventana de respuesta. Anotarlo
como pendiente es más honesto que rellenarlo con marcadores de posición.
