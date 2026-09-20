# Configuración de despliegue — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-pipeline (Operation) · **Alcance**: bugfix · **Profundidad**: Minimal
**Fecha**: 2026-09-19

---

## Qué es este documento, y qué no es

Es el procedimiento real para llevar esta corrección al entorno donde corre la
aplicación. **No es una configuración de CI/CD**, y decir lo contrario sería
inventar infraestructura que no existe.

Los hechos que lo determinan, verificados sobre el repositorio y confirmados
contigo en `deployment-pipeline-questions.md`:

| Hecho | Evidencia |
|---|---|
| No hay integración continua de ningún tipo | No existe `.github/workflows/` ni equivalente; la etapa CI Pipeline está fuera de este alcance por diseño |
| El destino es local, no remoto | Respuesta Q1 = A. El repositorio no declara registro de imágenes, servidor ni variables de producción |
| El código se monta, no se copia | `docker-compose.yml` monta `.:/var/www` en `app` y en `nginx` |
| La entrega es `git pull` en el destino | Respuesta Q2 = A |
| Una sola persona despliega y verifica | Respuesta Q3 = A |
| Esta corrección no toca el esquema | Restricción C1; `source-manifest.json` no incluye ninguna migración |

## Entradas heredadas: ninguna, y por qué

Las cuatro entradas que esta etapa declara —`ci-config`, `quality-gates`,
`infrastructure-specification`, `cicd-pipeline`— **no existen**, y su ausencia
está prevista: el alcance `bugfix` salta CI Pipeline e Infrastructure Design.
No se ha inventado ninguna. El diseño se apoya en lo que sí existe:

- `docker-compose.yml` y `Dockerfile` — la topología real de ejecución.
- `../../construction/build-and-test/build-instructions.md` — los comandos de
  construcción y sus prerrequisitos.
- `../../construction/build-and-test/test-results.md` — la evidencia de que
  esta corrección está verificada: `OK (22 tests, 108 assertions)`.
- `../../construction/code-generation/source-manifest.json` — los archivos que
  cambian, que es exactamente lo que hay que desplegar y lo que hay que poder
  revertir.

## Topología de ejecución

Cuatro servicios en una red de puente, definidos en `docker-compose.yml`:

```
                    host
                      |
              puerto 8000  puerto 8080  puerto 3306
                      |         |           |
    +-----------------v---------v-----------v-----------+
    |              dentissa_network (bridge)            |
    |                                                   |
    |  +---------+     +---------+     +-------------+  |
    |  | nginx   |---->| app     |---->| db          |  |
    |  | :80     |     | php-fpm |     | mariadb     |  |
    |  | alpine  |     | :9000   |     | 10.11       |  |
    |  +---------+     +---------+     +-------------+  |
    |       |               |                 ^         |
    |       |               |                 |         |
    |       +---- monta ----+          +-------------+  |
    |            .:/var/www            | phpmyadmin  |  |
    |                                  | :80         |  |
    |                                  +-------------+  |
    +---------------------------------------------------+
```

<!-- Texto alternativo: nginx escucha en el puerto 8000 del host y pasa las
     peticiones PHP a app (php-fpm) por el puerto 9000. app consulta db
     (MariaDB 10.11) que expone el 3306. phpmyadmin, en el puerto 8080 del
     host, también consulta db. nginx y app montan el directorio del
     repositorio en /var/www. -->

La consecuencia que gobierna todo el procedimiento: **el código fuente está
montado desde el host**, no horneado en la imagen. Un cambio en un archivo PHP o
Blade es visible en cuanto el archivo cambia en disco. El `Dockerfile` sí hace
`COPY . .`, pero el montaje de volumen lo tapa en ejecución, así que **no hace
falta reconstruir la imagen para desplegar esta corrección**.

## Procedimiento de despliegue

Diez pasos. Los tres primeros son preparación y red de seguridad; los siete
restantes son el despliegue propiamente dicho.

**El orden importa y no es el intuitivo.** Se limpia la caché de vistas
*antes* de construir el front-end, y se recompilan todas las vistas en medio.
El paso 7 explica por qué; saltárselo produce una hoja de estilos incompleta sin
ningún error visible.

### Preparación

**1. Confirmar que el árbol de trabajo está limpio y en la rama correcta.**

```bash
git status
git branch --show-current
```

Si hay cambios sin confirmar en el destino, decidir qué hacer con ellos antes de
seguir. Un `git pull` sobre un árbol sucio falla o mezcla, y ninguna de las dos
cosas es lo que quieres a mitad de un despliegue.

**2. Respaldar la base de datos.** Es obligatorio, no opcional. El detalle del
comando y la restauración están en `rollback-runbook.md` § «Respaldo previo».

```bash
docker compose exec -T db mysqldump -uroot --all-databases > respaldo-$(date +%Y%m%d-%H%M%S).sql
```

**3. Anotar el commit actual.** Es el punto de retorno.

```bash
git rev-parse HEAD > .ultimo-despliegue.txt
```

### Despliegue

**4. Traer el código.**

```bash
git pull
```

**5. Actualizar dependencias solo si cambiaron.** Esta corrección **no añade
ninguna**, ni de Composer ni de npm, así que normalmente se salta. Ejecútalo
solo si `composer.lock` o `package-lock.json` aparecen en el diff del `git pull`.

```bash
docker compose exec app composer install --no-interaction
npm install
```

**6. Limpiar las cachés de Laravel. Este paso no es opcional.**

```bash
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan config:clear
```

Las vistas compiladas en `storage/framework/views/` todavía referencian la ruta
`admin.usuarios.destroy`, que esta corrección **elimina**. Si te saltas este
paso, el listado de usuarios lanza una excepción de ruta no definida y la
pantalla queda inservible. Es el modo de fallo más probable de todo este
procedimiento.

**7. Recompilar TODAS las vistas antes de construir el front-end.**

```bash
docker compose exec app php artisan view:cache
```

**Este paso parece opcional y no lo es.** `resources/css/app.css` declara las
vistas compiladas como fuente de Tailwind:

```
@source '../../storage/framework/views/*.php';
```

Es decir, Tailwind decide qué reglas CSS emitir mirando, entre otras cosas, las
plantillas ya renderizadas. Sirve para capturar clases que se componen en
tiempo de ejecución (`class="badge badge-{{ $estado }}"`) y que no son visibles
en el código fuente. La consecuencia es que **el CSS depende de qué páginas se
hubieran visitado antes de construir**:

| Vistas compiladas al construir | Tamaño del CSS |
|---|---|
| 0, justo después de `view:clear` | 40,10 kB |
| Las 97, tras `view:cache` | 56,02 kB |

Medido el 2026-09-19 durante el despliegue. **Faltaba cerca del 28 % de la hoja
de estilos**, y lo que falta son precisamente las clases dinámicas: estados de
cita, colores de agenda, variantes de insignia. `view:cache` compila las 97
plantillas de una vez y hace la construcción reproducible.

**8. Construir los artefactos de front-end.** En el host, no en el contenedor,
y **después** del paso anterior.

```bash
npm run build
```

Debe terminar con `built in ...` y salida 0. Deja `public/build/manifest.json` y
los `assets/app-*.{css,js}`, que están montados y por tanto visibles al
instante. Comprueba que el CSS ronda los 56 kB: si sale alrededor de 40 kB, te
saltaste el paso 7 y la hoja de estilos está incompleta.

**9. Verificar que la aplicación responde.**

```bash
docker compose ps
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/login
```

`docker compose ps` debe mostrar los cuatro servicios en `running`; el `curl`
debe devolver `200`.

**10. Ejecutar la comprobación de humo.** Definida en `deployment-strategy.md`
§ «Comprobación de humo». Si falla, `rollback-runbook.md`.

## Qué se despliega exactamente

Trece archivos, de `source-manifest.json` más la comprobación de vistas añadida
en Build and Test. Ninguno es una migración.

| Archivo | Papel |
|---|---|
| `routes/web.php` | Orden y restricción de las rutas de usuarios (TD-11, TD-12) |
| `app/Http/Controllers/UsuarioController.php` | Validación, contraseña aleatoria, baja lógica (TD-16) |
| `app/Http/Controllers/ServiciosController.php` | Precio y color que dejan de machacarse (TD-8) |
| `resources/views/usuarios/create.blade.php` | Selector de rol con las cuatro opciones |
| `resources/views/usuarios/index.blade.php` | Modal de desactivación, sin promesa de borrado |
| `resources/views/servicios/create.blade.php` | Campos de precio y color |
| `resources/views/servicios/edit.blade.php` | Campos de precio y color precargados |
| `phpunit.xml` | Base de pruebas MariaDB en vez de SQLite |
| `database/testing/bootstrap-test-db.sh` | Script de preparación de la base de pruebas |
| `tests/Feature/AdminUsuariosRutasTest.php` | Regresión de tabla de rutas |
| `tests/Feature/ServiciosPrecioTest.php` | Regresión de precio y color |
| `tests/Feature/UsuariosAdminTest.php` | Regresión de usuarios |
| `tests/Feature/AdminVistasSmokeTest.php` | Humo sobre las vistas de administración |
| `tests/Feature/ExampleTest.php` | Ejemplo de Laravel corregido |

Los seis últimos son código de prueba: no afectan al comportamiento en
ejecución, pero viajan con el despliegue y sirven para verificarlo en destino.

## Puertas de calidad

No hay puerta automatizada, porque no hay pipeline que la ejecute. La puerta es
manual y la ejecuta la misma persona que despliega (respuesta Q3 = A):

| Puerta | Criterio | Cuándo | Si falla |
|---|---|---|---|
| Suite de pruebas | `OK (22 tests, 108 assertions)`, cero fallos | Antes del `git pull`, en la máquina de desarrollo | No desplegar |
| Construcción de front-end | `npm run build` salida 0 | Paso 6 | Detener, revisar la salida de Vite |
| Servicios arriba | Los cuatro en `running` | Paso 8 | `docker compose up -d` y volver a comprobar |
| Comprobación de humo | Todos los puntos en verde | Paso 9 | `rollback-runbook.md` |

**Una puerta que no se ejecuta no es una puerta.** Al no haber CI, nada impide
desplegar con la suite en rojo salvo la disciplina de quien despliega. Si esto
llega a tener un servidor de verdad, lo primero que merece automatizarse es la
primera fila de esa tabla; está anotado en `deployment-strategy.md`
§ «Qué haría falta para graduarse».

## Variables de entorno y secretos

`.env` **no está en el repositorio** y no debe estarlo: se deriva de
`.env.example`. Nada de lo que toca esta corrección introduce una variable
nueva, así que no hay que tocar `.env` al desplegar.

Dos apuntes de seguridad que no son de esta corrección pero conviene no callar,
porque este documento es donde alguien los buscaría:

- `docker-compose.yml` fija `MYSQL_ROOT_PASSWORD: root` y
  `MYSQL_ALLOW_EMPTY_PASSWORD: "yes"` en claro. Es aceptable en un entorno local
  y **no lo es** en cuanto la base escuche en una red compartida.
- El puerto 3306 y phpMyAdmin en el 8080 están publicados al host. En local es
  cómodo; en una máquina accesible desde fuera son dos superficies de ataque.

Ninguno de los dos se cambia aquí: quedan registrados como puntos abiertos en
`deployment-strategy.md` § «Riesgos que este despliegue no resuelve».
