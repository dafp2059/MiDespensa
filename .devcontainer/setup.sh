#!/usr/bin/env bash
# Prepara la vista previa en GitHub Codespaces: MariaDB, extensiones de PHP,
# base de datos y app/config.php. Se ejecuta una sola vez al crear el codespace.
set -euo pipefail
cd "$(dirname "$0")/.."

sudo apt-get update -y
sudo apt-get install -y mariadb-server libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev
if ! php -m | grep -qi '^pdo_mysql$'; then
  sudo docker-php-ext-install pdo_mysql
fi
if ! php -m | grep -qi '^gd$'; then
  sudo docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
  sudo docker-php-ext-install gd
fi

# Permitir fotos y videos grandes en la vista previa.
printf "upload_max_filesize=100M\npost_max_size=110M\nmemory_limit=256M\n" | sudo tee /usr/local/etc/php/conf.d/ingespro.ini >/dev/null

sudo service mariadb start
sudo mysql -e "CREATE DATABASE IF NOT EXISTS ingespro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'ingespro'@'localhost' IDENTIFIED BY 'ingespro';
  GRANT ALL ON ingespro.* TO 'ingespro'@'localhost'; FLUSH PRIVILEGES;"
if [ -z "$(sudo mysql -N -e "SHOW TABLES FROM ingespro LIKE 'properties'")" ]; then
  sudo mysql ingespro < database/schema.sql
fi

if [ ! -f app/config.php ]; then
  BASE_URL="http://localhost:8000"
  if [ -n "${CODESPACE_NAME:-}" ]; then
    BASE_URL="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
  fi
  sed -e "s#'base_url' => 'https://ingesproinmobiliaria.com'#'base_url' => '${BASE_URL}'#" \
      -e "s#'noindex' => false#'noindex' => true#" \
      -e "s#'name' => 'usuario_ingespro'#'name' => 'ingespro'#" \
      -e "s#'user' => 'usuario_ingespro'#'user' => 'ingespro'#" \
      -e "s#'pass' => 'CAMBIAR'#'pass' => 'ingespro'#" \
      app/config.sample.php > app/config.php
fi
mkdir -p public_html/uploads
echo "Listo. La web queda en ${BASE_URL:-http://localhost:8000}"
