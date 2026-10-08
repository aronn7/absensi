# SMKN 20 — Sistem Absensi Digital Sekolah

Aplikasi absensi digital berbasis **Laravel 12 + Blade + Tailwind CSS 4 + MySQL** dengan fitur:

- Login multi-peran: **Admin**, **Guru (wali kelas)**, **Murid**
- Absensi QR: kartu QR per murid, scanner kamera/unggah gambar, validasi server-side (token terenkripsi)
- Absensi manual oleh wali kelas (kelas dibuka dengan PIN)
- Permohonan izin/sakit dengan unggah bukti (disimpan privat)
- Penandaan alfa otomatis setelah deadline (`attendance:mark-absent`)
- Rekap bulanan (`reports:monthly`), ekspor **XLSX** (PhpSpreadsheet) dan **PDF** kartu (DomPDF)
- Notifikasi wali murid, kalender akademik, audit log

---

## 1. Prasyarat

| Komponen  | Versi                | Catatan                                            |
|-----------|----------------------|----------------------------------------------------|
| PHP       | ≥ 8.2 (diuji 8.4)    | Ekstensi wajib: `pdo_mysql`, `gd`, `zip`, `mbstring` |
| Composer  | 2.x                  |                                                    |
| Node/npm  | ≥ 18                 | untuk Vite + Playwright                            |
| MySQL     | 5.7+/8.x (port bebas)| Database `smkn20_absensi` (dev memakai port 3307)  |

Pada Windows + Laragon, pastikan `extension=zip` dan `extension=gd` aktif di `php.ini`
(`C:\laragon\bin\php\php-8.4.x\php.ini`).

## 2. Instalasi

```bash
# 1. Dependensi PHP
composer install

# 2. Environment
copy .env.example .env          # Windows (PowerShell: Copy-Item .env.example .env)
php artisan key:generate

# 3. Konfigurasi database di .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306            # 3307 pada setup Laragon lokal ini
#    DB_DATABASE=smkn20_absensi
#    DB_USERNAME=root
#    DB_PASSWORD=
# Buat database bila belum ada:
#    mysql -u root -P 3307 -e "CREATE DATABASE smkn20_absensi CHARACTER SET utf8mb4;"

# 4. Migrasi + seed (akun demo, murid, kelas, kartu QR)
php artisan migrate --seed

# 5. Dependensi frontend + build
npm install
npm run build
```

## 3. Menjalankan Aplikasi

```bash
php artisan serve                # default: http://127.0.0.1:8000
# atau seret port lain:
php artisan serve --port=8000
```

Buka `http://127.0.0.1:8000`. Halaman login per peran:

- Admin: `/login/admin`
- Guru: `/login/teacher`
- Murid: `/login/student`

### Akun demo (seed default, password `Sekolah123!`)

| Peran  | Identitas              |
|--------|------------------------|
| Admin  | ID Admin: `admin`      |
| Guru   | ID: `G001` (wali XI RPL 1, PIN kelas `246810`) |
| Murid  | Email: `murid1@sekolah.test`, NISN `0000001001` |

### Penjadwal otomatis (dev)

Jalankan di terminal terpisah:

```bash
php artisan schedule:work
```

- `attendance:mark-absent` — setiap menit setelah deadline, menandai murid yang belum scan sebagai alfa.
- `reports:monthly` — rekap bulanan otomatis (akhir bulan / tanggal 1).

### Mode PWA (install ke layar utama HP)

Aplikasi sudah berupa **PWA**: bisa "di-install" dari browser ke layar utama Android/iOS
seperti aplikasi native, tanpa Play Store/App Store.

Komponen:

| Berkas                    | Fungsi                                             |
|---------------------------|----------------------------------------------------|
| `public/manifest.json`    | Metadata app (nama, ikon, warna, `standalone`)     |
| `public/sw.js`            | Service worker: offline fallback + cache aset      |
| `public/offline.html`     | Halaman "tidak ada koneksi"                        |
| `public/icons/`           | Ikon 192/512 + maskable                            |
| `make-icons.php`          | Generator ikon (jalankan `php make-icons.php`)     |

Cara install di HP:
1. Buka alamat aplikasi di Chrome/Safari (**HTTPS** atau `localhost` — wajib).
2. Android: menu ⋮ → **Tambahkan ke layar utama** / "Install app".
   iOS Safari: tombol Share → **Tambahkan ke Layar Utama**.
3. Ikon muncul di homescreen; dibuka dalam jendela penuh tanpa address bar.

Catatan: mode offline hanya menampilkan halaman fallback — pencatatan absensi selalu
butuh koneksi karena divalidasi server. Untuk produksi, pasang HTTPS (mis. Let's Encrypt).
## 4. Pengujian Otomatis

### 4.1 PHPUnit (Unit + Feature, database SQLite in-memory)

```bash
php artisan test
```

Hasil saat ini: **28 test / 200+ assertion — lulus semua**. Mencakup login tiap peran,
validasi QR, duplikasi absen, hitungan telat, izin/sakit + bukti privat, PIN wali kelas,
ekspor XLSX asli, proteksi formula Excel, unduh PDF kartu, dan idempotensi perintah bulanan.

### 4.2 Playwright (E2E browser, memakai database MySQL `.env`)

```bash
# terminal 1
php artisan serve --port=8000

# terminal 2
npx playwright test
```

Konfigurasi ada di `playwright.config.js` (base URL dapat dioverride lewat `E2E_BASE_URL`).
Skenario ada di `tests/browser/school.spec.js`:

1. Dashboard admin desktop + grafik + filter + unduh laporan Excel
2. Navigasi murid mobile, form izin/sakit, preview & unduh kartu PDF
3. Buka kelas wali dengan PIN
4. Scanner gambar QR memanggil validasi server
5. Alur decode QR tanpa kamera fisik (memakai aset `.qa/demo-qr.png` / `frame1.png` hasil `.qa/demo-camera.y4m`)
6. PWA: manifest terpasang dan service worker terdaftar (`_pwa.spec.js`)
7. Skenario bantu `_decode.spec.js` untuk audit dekoder

Screenshot/unduhan QA disimpan di `.qa/`; laporan HTML Playwright di `playwright-report/`.

## 5. Pemeriksaan MySQL

Verifikasi cepat koneksi & isi database:

```bash
php artisan tinker --execute="echo App\Models\Student::count();"
# atau
php artisan db:show
```

Status terakhir (port 3307, database `smkn20_absensi`): 22 tabel, 36 murid, 6 guru,
3 kelas, 1.128 catatan absensi — data seed sehat (ringkasan: `.qa/mysql-health.txt`).

## 6. Struktur Penting

```
app/Http/Controllers/    Controller per peran + scanner + laporan
app/Models/              User, Student, Teacher, Attendance, AbsenceRequest, ...
database/migrations/     Skema MySQL (22 tabel)
database/seeders/        DatabaseSeeder (akun demo)
resources/views/         Blade + Tailwind 4 (Vite)
resources/js/app.js      Interaksi: scanner html5-qrcode, chart.js, dsb.
routes/web.php           Rute dengan middleware per peran
tests/Feature/           PHPUnit feature suite
tests/browser/           Playwright E2E
```

## 7. Catatan Operasional

- **Sesi & cache** memakai driver `database` — pastikan tabel `sessions`/`cache` ikut termigrasi.
- **Bukti izin/sakit** disimpan di disk `local` (privat); akses hanya via kontroler berotorisasi.
- **Token QR** unik dan terenkripsi, bukan nomor identitas; pindaian ulang tidak menggandakan absen.
- Kamera scanner butuh konteks aman (HTTPS atau `localhost`).
- `.qa/` dan `test-results/` hanya artefak pengujian; aman dihapus kapan saja.
