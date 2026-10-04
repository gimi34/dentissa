# Producción en el servidor propio

Guía paso a paso para preparar el servidor (Ubuntu Server 24.04), desplegar
Dentissa y abrirla a internet con Cloudflare Tunnel (spec 030, ADR 0005).
Todos los comandos se ejecutan **en el servidor, por SSH, desde la cuenta de
despliegue** (la cuenta creada al instalar Ubuntu), salvo los marcados "en tu PC".

> **Nunca pegues en el chat** el token del túnel, contraseñas, `APP_KEY` ni el
> contenido de los archivos de entorno. Para pedir ayuda, pega solo la salida de
> los scripts, que no muestra secretos.

| Ruta en el servidor | Contenido |
|---|---|
| `/opt/dentissa/app` | clon del repositorio (solo lectura desde GitHub) |
| `/opt/dentissa/entorno` | `app.env`, `db.env` y `tunnel.env` (carpeta 700, archivos 600, **fuera del clon**) |
| `/var/backups/dentissa` | copias de la base (carpeta 700, copias 600, las 14 más recientes) |

## 1. Preparar el servidor (una sola vez)

1. **En tu PC**, copia tu clave pública al servidor y comprueba que entras sin
   contraseña:
   ```bash
   ssh-copy-id <cuenta>@<IP del servidor>
   ssh <cuenta>@<IP del servidor>
   ```
2. Todavía no hay clon en el servidor: **en tu PC**, desde tu copia del
   repositorio en la rama o tag que vas a desplegar, envía solo el script; luego,
   en el servidor, ejecútalo con la red local desde la que vas a administrar:
   ```bash
   scp deploy/preparar-servidor.sh <cuenta>@<IP del servidor>:   # en tu PC
   sudo bash preparar-servidor.sh 192.168.1.0/24                  # en el servidor
   ```
   Si la cuenta no tiene clave autorizada, el script se detiene sin cambiar nada.
3. **Antes de cerrar la sesión**, abre otra por SSH y comprueba que entras con tu
   clave. Después cierra sesión y vuelve a entrar para usar `docker` sin `sudo`.

## 2. Clave de despliegue de solo lectura y clon

1. Crea una clave solo para leer el repositorio:
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/dentissa_deploy -N "" -C "dentissa-servidor"
   cat ~/.ssh/dentissa_deploy.pub
   ```
2. En GitHub: repositorio → **Settings → Deploy keys → Add deploy key**, pega la
   clave pública y deja **sin marcar** "Allow write access".
3. Dile a SSH que use esa clave para GitHub y clona en `/opt/dentissa/app`:
   ```bash
   printf 'Host github.com\n  IdentityFile ~/.ssh/dentissa_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
   chmod 600 ~/.ssh/config
   git clone git@github.com:gimi34/dentissa.git /opt/dentissa/app
   cd /opt/dentissa/app
   ```

## 3. Túnel de Cloudflare

1. En Cloudflare: **Zero Trust → Networks → Tunnels → Create a tunnel →
   Cloudflared**, nombre `dentissa`, entorno **Docker**. De la orden que muestra,
   copia **solo el token** (la cadena larga tras `--token`).
2. En **Public Hostname**, añade el subdominio (p. ej. `dentissa.tudominio.com`)
   con servicio **HTTP** y URL **`web:80`**.
3. Recomendado, como refuerzo de la redirección a https que ya hace nginx: en el
   dominio, **SSL/TLS → Edge Certificates → Always Use HTTPS** activado.
4. **No arranques el túnel todavía**: se abre en la liberación (sección 9).

## 4. Archivos de entorno

Crea los tres archivos en `/opt/dentissa/entorno` (nunca dentro del clon) con
`nano`, y ponles permisos 600. **Una variable por línea, con la forma
`NOMBRE=valor`**: sin comas, sin espacios alrededor del `=` y sin comillas. En
`nano`, guarda con `Ctrl+O` y `Enter`, y sal con `Ctrl+X`. Para las contraseñas,
`openssl rand -hex 24` da valores sin caracteres problemáticos.

**`app.env`** (pon tus valores donde van los `<…>`; `APP_KEY` se rellena después):
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<subdominio>
APP_KEY=
LOG_LEVEL=info
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
CACHE_STORE=database
DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dentissa
DB_USERNAME=dentissa_app
DB_PASSWORD=<la misma que MARIADB_PASSWORD de db.env>
GOOGLE_CLIENT_ID=<client id>
GOOGLE_CLIENT_SECRET=<client secret>
GOOGLE_REDIRECT_URI=https://<subdominio>/auth/google/callback
```

**`db.env`** (dos contraseñas distintas; la base `dentissa` y el usuario
`dentissa_app` ya vienen en el Compose):
```
MARIADB_PASSWORD=<contraseña del usuario de la base>
MARIADB_ROOT_PASSWORD=<otra contraseña, la de root>
```

**`tunnel.env`** (solo la cadena larga que va tras `--token` en Cloudflare):
```
TUNNEL_TOKEN=<token>
```

```bash
cd /opt/dentissa/entorno
nano app.env
nano db.env
nano tunnel.env
chmod 600 app.env db.env tunnel.env
ls -l
grep -vnE '^[A-Z_]+=' app.env db.env tunnel.env   # no debe imprimir nada
cut -d= -f1 app.env                               # solo los nombres
```

**Generar `APP_KEY`** sin arrancar la base y guardarla en `app.env` sin que
aparezca en pantalla (la primera vez construye la imagen):
```bash
cd /opt/dentissa/app
CLAVE=$(docker compose -f docker-compose.prod.yml run --rm --no-deps -u www-data app php artisan key:generate --show | tail -n 1 | tr -d '\r')
echo "$CLAVE" | grep -c '^base64:'                 # debe dar 1; si da 0, no sigas
sed -i '/^APP_KEY=/d' /opt/dentissa/entorno/app.env
printf 'APP_KEY=%s\n' "$CLAVE" >> /opt/dentissa/entorno/app.env
unset CLAVE
grep -c '^APP_KEY=base64:' /opt/dentissa/entorno/app.env   # debe dar 1
```
Esta orden crea vacío el volumen de la base; `desplegar.sh` lo detecta y trata
igualmente el primer despliegue como tal.

## 5. Google

En Google Cloud Console → **APIs & Services → Credentials** → el cliente OAuth
de Dentissa → **Authorized redirect URIs**, añade
`https://<subdominio>/auth/google/callback`. Sin esto, el login con Google falla
en producción.

## 6. Ensayo y primer despliegue (sin exponer)

Mientras el túnel está parado, nadie de fuera llega a la aplicación. Antes de
cada ensayo (desplegar, volver atrás, restaurar la base o reiniciar el servidor)
**confirma en el chat tu aprobación**: queda anotada en `tasks.md`.

```bash
cd /opt/dentissa/app
bash deploy/desplegar.sh <tag o commit>      # el primero anuncia "sin copia previa"
docker compose -f docker-compose.prod.yml exec -u www-data app php artisan dentissa:rotar-credenciales --confirmar
bash deploy/verificar.sh --interna
```

`dentissa:rotar-credenciales` muestra **una sola vez** la contraseña nueva de cada
cuenta del volcado: guárdala en tu gestor de contraseñas, nunca en el chat ni en
el repositorio. `desplegar.sh` termina con `verificar.sh --interna`. **En el
primer despliegue esa verificación falla siempre en "contraseñas del README"**,
porque las credenciales del volcado aún no están rotadas: el script lo avisa y
no propone volver atrás (no hay versión anterior). Rota las credenciales y
repite `bash deploy/verificar.sh --interna`; entonces debe salir todo OK. En
los despliegues siguientes, si algo falla, te dice cómo volver atrás.

### Probar la aplicación sin exponerla

`web` no publica puertos. Para entrar desde tu PC, abre un túnel SSH a su IP en
la red de Docker:

```bash
# En el servidor: IP de web
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' dentissa-prod-web-1
# En tu PC:
ssh -L 8081:<IP de web>:80 <cuenta>@<IP del servidor>
```

Abre **`http://localhost:8081`** (exactamente `localhost`). La cookie de sesión
es `Secure` y el navegador solo la envía por http cuando el sitio es
`localhost`; con la IP del servidor el login fallaría sin motivo aparente.

## 7. Copias de la base

`desplegar.sh` hace una copia antes de cada despliegue (salvo el primero). A mano:

```bash
bash deploy/respaldar.sh
```

Tras cada despliegue, llévate la copia más reciente a tu PC (**en tu PC**):

```bash
scp <cuenta>@<IP del servidor>:/var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz .
```

Las copias contienen datos personales y de salud y aún van sin cifrar (spec 031):
guárdalas en una carpeta solo tuya.

Restaurar una copia (borra la base y la recrea desde la copia; pide confirmación):

```bash
docker compose -f docker-compose.prod.yml stop app web
bash deploy/restaurar.sh /var/backups/dentissa/dentissa-AAAAMMDD-HHMMSS.sql.gz
docker compose -f docker-compose.prod.yml up -d app web
```

## 8. Volver a la versión anterior

```bash
bash deploy/volver.sh <tag o commit anterior>            # sin cambios de base
bash deploy/volver.sh <tag o commit anterior> <copia>    # si la versión nueva migró la base
```

Con copia, `volver.sh` para `app` y `web`, restaura la base y levanta la versión
anterior. Termina con `verificar.sh --interna`. Para dejar la aplicación sin
exposición al instante: `docker compose -f docker-compose.prod.yml stop cloudflared`.

## 9. Abrir el túnel (liberación)

Solo en `/release`, con aprobación explícita:

```bash
bash deploy/desplegar.sh vX.Y.Z
docker compose -f docker-compose.prod.yml up -d cloudflared
bash deploy/verificar.sh           # ahora también por la dirección pública
```

Desde entonces `cloudflared` vuelve solo tras un reinicio. Los scripts de
despliegue nunca lo arrancan ni lo paran.
