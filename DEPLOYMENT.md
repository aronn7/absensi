# Panduan Deployment Produksi — Sistem Absensi Digital SMKN 20

Panduan membawa aplikasi dari mesin development (Laragon/`php artisan serve`)
ke server produksi (VPS Ubuntu/Debian, atau hosting cPanel) termasuk HTTPS dan
cron untuk fitur otomatis.

---

## 1. Persiapan berkas (di mesin development)
### Cara cepat (disarankan): paket rilis + skrip deploy otomatis

Dua berkas sudah disiapkan:

- **Paket rilis**: `dist/absensi-release-<tanggal>.zip` (±22 MB) — seluruh kode + `vendor/`
  + aset build + ikon PWA + `.env.production.example`. **Tanpa** `node_modules`, `.env`,
  artefak tes. Disertai berkas `.sha256` untuk verifikasi integritas.
- **Skrip deploy**: `deploy-server.sh` — dijalankan SEKALI di server, otomatis: pasang
  paket (nginx/php/mysql/certbot), ekstrak, composer install, buat database + password acak,
  buat `.env`, key + migrate + seed, cache, konfigurasi nginx, **HTTPS Let's Encrypt**, dan cron.

Cara pakai (paling singkat):

```bash
# di mesin development (Windows): buat paket
php make-release.php          # hasil: dist/absensi-release-*.zip

# upload zip + deploy-server.sh ke server, lalu di server:
sudo bash deploy-server.sh /tmp/absensi-release-XXXX.zip absensi.sekolah.sch.id
```

Skrip mencetak password database di akhir — catat, lalu ikuti "Langkah lanjutan"
yang ditampilkannya (ganti password admin default, dsb).

> Catatan: `make-release.php` membuat ulang paket kapan pun diperlukan
> (jalankan `php make-release.php` setiap kali kode berubah sebelum upload).

### Cara manual (jika ingin kendali penuh)
```bash
# build aset frontend terbaru
npm run build

# (opsional) pastikan semua test lulus sebelum rilis
php artisan test
npx playwright test      # butuh server jalan di 127.0.0.1:8000
```

Berkas yang **di-upload** ke server: seluruh proyek **kecuali**
`node_modules/`, `.qa/`, `test-results/`, `playwright-report/`, `.env`
(`.env` dibuat manual di server).

## 2. Persiapan server (VPS Ubuntu/Debian + Nginx)

```bash
# paket dasar
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-gd \
    php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl unzip git

# composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# clone / salin proyek
sudo mkdir -p /var/www/absensi
# (git clone atau rsync proyek ke /var/www/absensi)
sudo chown -R $USER:www-data /var/www/absensi
```

### Database

```bash
sudo mysql -e "CREATE DATABASE smkn20_absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'absensi'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';"
sudo mysql -e "GRANT ALL PRIVILEGES ON smkn20_absensi.* TO 'absensi'@'localhost'; FLUSH PRIVILEGES;"
```

### Environment `.env` produksi

```ini
APP_NAME="Sistem Absensi Digital Sekolah"
APP_ENV=production          # penting: bukan local
APP_DEBUG=false
APP_URL=https://absensi.sekolah.sch.id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smkn20_absensi
DB_USERNAME=absensi
DB_PASSWORD=GANTI_PASSWORD_KUAT
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true  # aman karena sudah HTTPS
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=log             # ganti ke smtp bila notifikasi email diaktifkan
```

Lalu di server:

```bash
cd /var/www/absensi
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

> `make-icons.php` tidak perlu dijalankan di server — ikon PWA sudah ikut
> ter-commit di `public/icons/`.

## 3. Nginx + HTTPS

`/etc/nginx/sites-available/absensi`:

```nginx
server {
    listen 80;
    server_name absensi.sekolah.sch.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name absensi.sekolah.sch.id;
    root /var/www/absensi/public;       # WAJIB mengarah ke public/
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/absensi.sekolah.sch.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/absensi.sekolah.sch.id/privkey.pem;

    client_max_body_size 20M;           # unggah foto bukti izin/sakit

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

Sertifikat gratis (Let's Encrypt):

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d absensi.sekolah.sch.id
```

> **HTTPS wajib** untuk: kamera QR scanner (butuh secure context) dan
> install PWA ke layar utama HP. Setelah HTTPS aktif, set
> `SESSION_SECURE_COOKIE=true`.

## 4. Cron penjadwal otomatis (wajib di produksi)

`crontab -e` (user web, mis. `www-data`):

```cron
* * * * * cd /var/www/absensi && php artisan schedule:run >> /dev/null 2>&1
```

Menjalankan tiap menit:
- `attendance:mark-absent` — alfa setelah deadline
- `reports:monthly` — rekap bulanan

## 5. Queue & mail (opsional)

Notifikasi tersimpan di database (tabel `notifications`), tidak butuh queue
untuk berjalan. Bila nanti ingin email/push:

```bash
# .env: QUEUE_CONNECTION=database
sudo apt install -y supervisor
# /etc/supervisor/conf.d/absensi-worker.conf:
# [program:absensi-worker]
# command=php /var/www/absensi/artisan queue:work --sleep=3 --tries=3
# autostart=true autorestart=true user=www-data numprocs=1
sudo supervisorctl reread && sudo supervisorctl update
```

## 6. Hosting cPanel (alternatif tanpa VPS)

1. Upload proyek ke luar `public_html`, mis. `/home/user/absensi`.
2. Arahkan **document root domain/subdomain** ke `/home/user/absensi/public`.
3. Buat database MySQL dari cPanel, isi kredensial di `.env`
   (`APP_ENV=production`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`).
4. Di Terminal cPanel (jika tersedia SSH):
   `composer install --no-dev --optimize-autoloader && php artisan migrate --seed --force`
   lalu tiga perintah cache seperti pada bagian 2.
5. Cron via cPanel → **Cron Jobs**: `* * * * * cd /home/user/absensi && php artisan schedule:run`
6. HTTPS aktifkan lewat cPanel → **SSL/TLS Status** (AutoSSL/Let's Encrypt).

## 7. Update aplikasi di kemudian hari

```bash
cd /var/www/absensi
git pull                      # atau rsync berkas terbaru
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
```

## 8. Cek otomatis sebelum rilis

```bash
php check-production.php
```

Memeriksa APP_ENV/APP_DEBUG/APP_URL/HTTPS, cookie aman, password DB,
kelengkapan aset PWA, build Vite, dan tag manifest/service worker di layout.
Keluar 1 bila ada temuan � jalankan manual atau pasang di CI sebelum rilis.

## 9. Checklist sebelum dipakai warga sekolah

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` memakai `https://` dan cocok dengan sertifikat
- [ ] `php artisan migrate --seed --force` sukses; login admin berfungsi
- [ ] Ganti password akun admin default dari seed, nonaktifkan akun demo yang tidak dipakai
- [ ] Scanner QR berfungsi dari HP (kamera muncul, absen tercatat)
- [ ] PWA bisa "Tambahkan ke layar utama" dari HP
- [ ] Cron berjalan: cek tabel `attendances` terisi alfa setelah jam deadline
- [ ] Cadangan database harian (`mysqldump` via cron, atau fitur backup hosting)
