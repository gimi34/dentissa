#!/usr/bin/env bash
#
# Spec 030 (D16, CA3): prepara una sola vez el Ubuntu Server 24.04 recién
# instalado para la producción de Dentissa.
#
# Uso, desde la cuenta creada al instalar Ubuntu (la cuenta de despliegue) y
# con una sesión SSH de reserva abierta:
#
#     sudo bash deploy/preparar-servidor.sh 192.168.1.0/24
#
# El argumento es la red local desde la que se administra el servidor por SSH.
#
# Qué hace:
#   - actualiza el sistema y activa las actualizaciones automáticas de seguridad;
#   - instala Docker Engine y el plugin de Compose desde el repositorio oficial;
#   - cortafuegos: deniega todo lo entrante salvo SSH desde la red local;
#   - SSH solo con clave y sin root (solo si la cuenta ya tiene una clave
#     autorizada; si no, se detiene antes de cambiar nada);
#   - añade la cuenta de despliegue al grupo docker y crea /opt/dentissa,
#     /opt/dentissa/entorno (archivos de entorno, fuera del clon) y
#     /var/backups/dentissa.

set -euo pipefail

error() {
    echo "ERROR: $*" >&2
    exit 1
}

paso() {
    echo
    echo "==> $*"
}

main() {
    local red="${1:-}"

    # ── Comprobaciones previas: nada cambia si alguna falla ─────────────────
    [[ "${EUID}" -eq 0 ]] || error "ejecútalo con sudo: sudo bash deploy/preparar-servidor.sh <red local>"
    [[ -n "${SUDO_USER:-}" && "${SUDO_USER}" != "root" ]] \
        || error "ejecútalo con sudo desde tu cuenta (la de despliegue), no como root directamente"
    [[ "${red}" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}/[0-9]{1,2}$ ]] \
        || error "indica la red local en formato CIDR, por ejemplo: 192.168.1.0/24"

    local cuenta="${SUDO_USER}"
    local inicio
    inicio="$(getent passwd "${cuenta}" | cut -d: -f6)"
    local claves="${inicio}/.ssh/authorized_keys"

    if ! grep -qE '^(ssh-(ed25519|rsa)|ecdsa-sha2-|sk-)' "${claves}" 2>/dev/null; then
        error "la cuenta ${cuenta} no tiene ninguna clave en ${claves}.
Copia antes tu clave pública desde tu PC (ssh-copy-id ${cuenta}@<IP del servidor>),
comprueba que entras sin contraseña y vuelve a ejecutar este script."
    fi

    # ── Sistema y actualizaciones automáticas ───────────────────────────────
    paso "Actualizando el sistema"
    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get -y upgrade
    apt-get install -y ca-certificates curl gnupg ufw unattended-upgrades

    paso "Activando las actualizaciones automáticas de seguridad"
    cat > /etc/apt/apt.conf.d/20auto-upgrades <<'CONF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
CONF
    systemctl enable --now unattended-upgrades

    # ── Docker Engine desde el repositorio oficial ──────────────────────────
    paso "Instalando Docker Engine"
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    # shellcheck disable=SC1091
    local codigo
    codigo="$(. /etc/os-release && echo "${VERSION_CODENAME}")"
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${codigo} stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    systemctl enable --now docker

    # ── Cortafuegos ─────────────────────────────────────────────────────────
    paso "Cortafuegos: todo lo entrante denegado salvo SSH desde ${red}"
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow from "${red}" to any port 22 proto tcp
    ufw --force enable
    ufw status verbose

    # ── SSH solo con clave ──────────────────────────────────────────────────
    paso "SSH: solo con clave y sin root"
    cat > /etc/ssh/sshd_config.d/10-dentissa.conf <<'CONF'
# Spec 030 (CA3): acceso solo con clave, sin contraseña y sin root.
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
CONF
    sshd -t || error "la configuración de SSH no es válida; revisa /etc/ssh/sshd_config.d/10-dentissa.conf"
    systemctl reload ssh

    # ── Cuenta de despliegue y carpetas ─────────────────────────────────────
    paso "Cuenta de despliegue (${cuenta}) y carpetas"
    usermod -aG docker "${cuenta}"
    install -d -m 750 -o "${cuenta}" -g "${cuenta}" /opt/dentissa
    install -d -m 700 -o "${cuenta}" -g "${cuenta}" /opt/dentissa/entorno
    install -d -m 700 -o "${cuenta}" -g "${cuenta}" /var/backups/dentissa
    ls -ld /opt/dentissa /opt/dentissa/entorno /var/backups/dentissa

    paso "Listo"
    echo "Antes de cerrar esta sesión, abre otra por SSH y comprueba que entras con tu clave."
    echo "Cierra sesión y vuelve a entrar para que ${cuenta} use docker sin sudo."
    echo "Siguiente paso: clonar el repositorio en /opt/dentissa/app (deploy/README.md)."
}

main "$@"
