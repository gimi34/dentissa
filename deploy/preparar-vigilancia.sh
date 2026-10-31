#!/usr/bin/env bash
#
# Spec 032 (CA1, CA2, CA4; TM10; D11): instala el temporizador de systemd que
# lanza el vigilante de avisos cada minuto.
#
#     sudo bash deploy/preparar-vigilancia.sh      # desde la cuenta de despliegue
#
# Qué hace (se puede repetir sin cambiar el resultado):
#   - comprueba que gpg y la clave pública de las copias están en su sitio;
#   - instala dentissa-vigilar.service (oneshot, con la cuenta de despliegue:
#     php artisan dentissa:vigilar dentro de app como www-data) y
#     dentissa-vigilar.timer (cada minuto), y los activa.
#
# Para pararlo: sudo systemctl disable --now dentissa-vigilar.timer

set -euo pipefail

CLAVE_PUBLICA="${DENTISSA_CLAVE_PUBLICA:-/opt/dentissa/entorno/copias.pub.asc}"

error() {
    echo "ERROR: $*" >&2
    exit 1
}

paso() {
    echo
    echo "==> $*"
}

main() {
    [[ "${EUID}" -eq 0 ]] || error "ejecútalo con sudo: sudo bash deploy/preparar-vigilancia.sh"
    local cuenta="${SUDO_USER:-}"
    [[ -n "${cuenta}" && "${cuenta}" != "root" ]] || error "ejecútalo con sudo desde la cuenta de despliegue, no como root"
    id -nG "${cuenta}" | grep -qw docker || error "la cuenta ${cuenta} no está en el grupo docker"

    paso "Copias cifradas: gpg y la clave pública"
    command -v gpg > /dev/null || error "falta gpg (lo instala deploy/preparar-servidor.sh)"
    [[ -r "${CLAVE_PUBLICA}" ]] || error "no se puede leer la clave pública ${CLAVE_PUBLICA} (deploy/README.md §7)"
    gpg --show-keys --with-colons -- "${CLAVE_PUBLICA}" 2> /dev/null | grep -q '^pub:' \
        || error "${CLAVE_PUBLICA} no es una clave pública válida"

    paso "Temporizador dentissa-vigilar (cada minuto, con la cuenta ${cuenta})"
    cat > /etc/systemd/system/dentissa-vigilar.service <<UNIDAD
[Unit]
Description=Dentissa: avisos de producción (spec 032)
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
User=${cuenta}
ExecStart=/usr/bin/docker compose -f /opt/dentissa/app/docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar
UNIDAD
    cat > /etc/systemd/system/dentissa-vigilar.timer <<UNIDAD
[Unit]
Description=Dentissa: vigilante de avisos cada minuto (spec 032)

[Timer]
OnCalendar=minutely

[Install]
WantedBy=timers.target
UNIDAD

    systemctl daemon-reload
    systemctl enable --now dentissa-vigilar.timer

    echo
    systemctl list-timers dentissa-vigilar.timer --no-pager
    echo
    echo "Envía ahora un aviso de prueba (deploy/README.md, avisos):"
    echo "    docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan dentissa:vigilar --prueba"
}

main "$@"
