# Preguntas — Pipeline de Despliegue

**Intención**: `260903-fix-admin-crud`
**Etapa**: deployment-pipeline (Operation) · **Alcance**: bugfix · **Profundidad**: Minimal
**Fecha**: 2026-09-19

---

## Lo que ya sé, para no preguntártelo

He inspeccionado el repositorio en vez de suponer. Esto es lo que encontré y no
necesito confirmar:

- **No hay CI de ningún tipo.** No existe `.github/workflows/` ni configuración
  equivalente. Las etapas de CI Pipeline e Infrastructure Design están fuera de
  este alcance por diseño, así que no hay artefactos que heredar.
- **El despliegue hoy es Docker Compose**: cuatro servicios (`app` PHP-FPM,
  `nginx` en el puerto 8000, `db` MariaDB 10.11, `phpmyadmin` en el 8080).
- **El código fuente va montado, no copiado.** `docker-compose.yml` monta
  `.:/var/www`, así que un cambio en PHP es visible sin reconstruir la imagen.
  El `Dockerfile` sí copia el proyecto, pero el montaje lo tapa en ejecución.
- **Esta corrección no toca el esquema de base de datos** (restricción C1), así
  que no hay migración que ejecutar ni que revertir. El volcado
  `database/dentissa.sql` solo se carga en la creación inicial del volumen.
- **Hay un paso obligatorio tras desplegar**: `php artisan view:clear` y
  `route:clear`. Las vistas compiladas todavía referencian la ruta
  `admin.usuarios.destroy`, que esta corrección elimina; sin limpiar la caché,
  el listado de usuarios revienta.
- **Los artefactos de front-end se construyen en el host** (`npm run build`) y
  quedan en `public/build/`, también montado.

---

## Q1. ¿Adónde se despliega esto de verdad?

Es la pregunta que decide todo lo demás. El repositorio solo describe un entorno
local de Docker Compose: no hay registro de imágenes, ni configuración de
servidor, ni variables de entorno de producción, ni nada que apunte a una
máquina remota.

- A. **Solo local.** Esta corrección se despliega levantando el Docker Compose en la máquina de quien lo use. No hay servidor remoto ni entorno compartido.
- B. **Un servidor único.** Hay una máquina (VPS, servidor de la clínica, hosting) donde esto corre de verdad, y el despliegue consiste en llevar el código allí.
- C. **Entornos separados.** Existe al menos una separación entre pruebas y producción, con promoción entre ambos.
- X. Other (please specify)

[Answer]: A

## Q2. ¿Cómo llega el código al sitio donde corre?

Condiciona si el runbook habla de `git pull`, de copiar archivos, o de
reconstruir una imagen.

- A. **`git pull` en el destino**, con el código montado como ahora. Es lo más coherente con el `docker-compose.yml` actual.
- B. **Reconstruir la imagen Docker** y reiniciar los servicios, sin montaje de código.
- C. **Copia manual de archivos** (FTP, SCP, panel de hosting).
- D. **Todavía no está resuelto**: documenta el procedimiento suponiendo la opción A y márcalo como pendiente de confirmar.
- X. Other (please specify)

[Answer]: A

## Q3. ¿Quién aprueba y verifica que el despliegue salió bien?

La regla de la organización dice que los despliegues a producción se aprueban a
mano. Quiero saber si aquí eso significa algo o si es un proyecto de una sola
persona.

- A. **Una sola persona** decide y verifica; la aprobación es implícita.
- B. **Dos pares de ojos**: alguien despliega y otra persona confirma que las pantallas funcionan.
- C. **Aprobación formal** de un responsable antes de tocar el entorno real.
- X. Other (please specify)

[Answer]: A

## Q4. ¿Qué debe cubrir el procedimiento de vuelta atrás?

Esta corrección toca nueve archivos y ninguna tabla, así que revertir es barato.
Lo que quiero acotar es hasta dónde llega el runbook.

- A. **Solo el código**: revertir los archivos y limpiar cachés. Suficiente, porque no hay cambio de esquema.
- B. **Código y datos**: incluir además cómo respaldar y restaurar la base antes de desplegar, por si una edición desde las pantallas corregidas dejara datos que no quieres conservar.
- C. **Código, datos y una comprobación de humo** documentada que diga explícitamente cuándo dar marcha atrás.
- X. Other (please specify)

[Answer]: B

---

## Consolidated Summary Confirmation

**¿Está todo correcto antes de que genere el artefacto?**

- Looks correct
- Request changes

[Answer]: Looks correct
