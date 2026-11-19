#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="/var/www/kriptografi.rifrain.tech"
DB_NAME="rifrain_crypto_demo"
DB_USER="rifrain_crypto"
DB_HOST="127.0.0.1"
APP_USER="rifan"
APP_GROUP="www-data"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Jalankan dengan sudo: sudo bash ${APP_ROOT}/bin/provision-server.sh" >&2
  exit 1
fi
if [[ ! -f "${APP_ROOT}/.env.example" || ! -f "${APP_ROOT}/bin/setup.php" ]]; then
  echo "Folder aplikasi tidak ditemukan di ${APP_ROOT}." >&2
  exit 1
fi
if [[ -e "${APP_ROOT}/.env" ]]; then
  echo "File .env sudah ada; tidak akan menimpa konfigurasi yang ada." >&2
  exit 1
fi
if ! mysql --protocol=socket --batch --skip-column-names -e 'SELECT 1' >/dev/null 2>&1; then
  echo "Akses MariaDB lokal sebagai root gagal. Pastikan MariaDB aktif dan akun root memakai socket authentication." >&2
  exit 1
fi

DB_EXISTS="$(mysql --protocol=socket --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '${DB_NAME}'")"
USER_EXISTS="$(mysql --protocol=socket --batch --skip-column-names -e "SELECT COUNT(*) FROM mysql.user WHERE User = '${DB_USER}' AND Host = '${DB_HOST}'")"
if [[ "${DB_EXISTS}" != "0" || "${USER_EXISTS}" != "0" ]]; then
  echo "Database atau akun ${DB_USER}@${DB_HOST} sudah ada; tidak akan mengubahnya." >&2
  exit 1
fi

DB_PASSWORD="$(openssl rand -hex 32)"
umask 027
cat > "${APP_ROOT}/.env" <<EOF
APP_ENV=production
APP_URL=https://kriptografi.rifrain.tech
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
EOF
chown "${APP_USER}:${APP_GROUP}" "${APP_ROOT}/.env"
chmod 0640 "${APP_ROOT}/.env"

mysql --protocol=socket <<SQL
CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASSWORD}';
GRANT SELECT, INSERT, CREATE ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;
SQL

if ! runuser -u "${APP_USER}" -- php "${APP_ROOT}/bin/setup.php"; then
  echo "Setup tabel gagal. Database dan .env dibiarkan agar dapat diperiksa; tidak ada data yang dihapus." >&2
  exit 1
fi

mysql --protocol=socket <<SQL
REVOKE INSERT, CREATE ON \`${DB_NAME}\`.* FROM '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;
SQL

echo "Database dan data demo siap. .env dibuat dengan izin terbatas."
echo "Salin storage/keys/private.pem ke perangkat pribadimu, lalu hapus salinan di server setelah kunci berhasil dicoba."
