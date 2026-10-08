#!/usr/bin/env bash
# =============================================================
# Deploy sekali-jalan untuk Sistem Absensi Digital SMKN 20
# Pemakaian:
#   1) Upload paket zip + file ini ke /tmp di server, lalu:
#      sudo bash deploy-server.sh /tmp/absensi-release-YYYYMMDD-HHMM.zip absensi.sekolah.sch.id
#   2) Skrip mengekstrak ke /var/www/absensi, siapkan .env, install
#      dependensi, migrate+seed, cache, pasang nginx + certbot + cron.
# Sebelum menjalankan: punya domain yang mengarah ke IP server.
# =============================================================
set -euo pipefail

ZIPFILE="${1:-}"
DOMAIN="${2:-}"
APP_DIR="/var/www/absensi"
DB_NAME="smkn20_absensi"
DB_USER="absensi"

if [[ -z "$ZIPFILE" || -z "$DOMAIN" ]]; then
    echo "Pemakaian: sudo bash $0 <file-release.zip> <domain.sekolah.sch.id>"
    exit 1
fi
[[ -f "$ZIPFILE" ]] || { echo "File zip tidak ditemukan: $ZIPFILE"; exit 1; }

echo "==> 1/9 Paket server (nginx, php-fpm, mysql, certbot)"
apt update -qq
apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-gd \
    php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl \
    unzip certbot python3-certbot-nginx

echo "==> 2/9 Ekstrak paket ke $APP_DIR"
rm -rf "$APP_DIR"
mkdir -p "$APP_DIR"
unzip -q "$ZIPFILE" -d "$APP_DIR"
cd "$APP_DIR"

echo "==> 3/9 Composer (tanpa paket dev)"
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> 4/9 Database"
DB_PASS="$(openssl rand -hex 16)"
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

echo "==> 5/9 Berkas .env"
if [[ ! -f .env ]]; then
    cp .env.production.example .env
    sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" .env
fi
chown root:www-data .env && chmod 640 .env

echo "==> 6/9 Key, migrate, cache"
php artisan key:generate --force
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

echo "==> 7/9 Nginx"
cat > /etc/nginx/sites-available/absensi <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;
    client_max_body_size 20M;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
EOF
ln -sf /etc/nginx/sites-available/absensi /etc/nginx/sites-enabled/absensi
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> 8/9 HTTPS (Let's Encrypt)"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m admin@$DOMAIN --redirect
sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
php artisan config:cache

echo "==> 9/9 Cron penjadwal"
( crontab -u www-data 2>/dev/null | grep -v 'schedule:run' ; \
  echo '* * * * * cd '"$APP_DIR"' && php artisan schedule:run >> /dev/null 2>&1' ) | crontab -u www-data -

cat <<SELESAI

=============================================
 DEPLOY SELESAI — https://$DOMAIN
=============================================
Kredensial database (disimpan di $APP_DIR/.env):
  DB: $DB_NAME   User: $DB_USER   Password: $DB_PASS

Langkah lanjutan (manual):
  1. Buka https://$DOMAIN/login/admin dan MASUK.
  2. SEGERA ganti password admin default dari seed.
  3. Nonaktifkan akun demo yang tidak dipakai (menu Guru/Murid).
  4. Uji scanner QR dari HP + install PWA ke layar utama.
  5. Jadwalkan backup database harian.
SELESAI
