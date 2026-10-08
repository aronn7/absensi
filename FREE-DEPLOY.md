# Hosting & Domain Gratis — Sistem Absensi Digital SMKN 20

Panduan membawa aplikasi online **tanpa biaya** (Rp0), memakai layanan berlapis gratis.
Ada dua jalur; pilih satu.

---

## Ringkasan pilihan

| Jalur | Biaya | Cocok untuk | Kelebihan | Kekurangan |
|---|---|---|---|---|
| **A. Oracle Cloud Always Free** (VPS) + domain sekolah | Rp0* | Instalasi "server sendiri" seperti produksi sungguhan | HTTPS penuh, cron, kontrol total | Perlu kartu untuk verifikasi akun; perlu domain |
| **B. Free shared hosting** (InfinityFree / Byet) | Rp0 | Sekolah tanpa domain, mulai cepat | Tanpa kartu, cPanel gratis, SSL subdomain gratis | Kuota terbatas, performa rendah, cron terbatas |
| **C. Komputer sekolah sebagai server** (LAN) | Rp0 | Absensi hanya dalam jaringan sekolah | Server fisik milik sekolah | Tidak bisa diakses dari luar; butuh PWA via HTTP caveat |

\* Oracle Always Free "gratis selamanya" untuk 2 VM kecil (AMD, 1/8 OCPU + 1 GB RAM) —
cukup untuk Laravel + MySQL satu sekolah.

> **Rekomendasi**: jika sekolah punya domain `sch.id` (biasanya sudah dimiliki sekolah),
> gunakan **Jalur A**. Jika tidak punya apa-apa dan ingin langsung jalan, gunakan **Jalur B**.

---

## Jalur A — Oracle Cloud Always Free (server penuh, gratis selamanya)

Layanan yang dipakai:
- **Compute VM** (Always Free): 2 instance AMD, ~1 GB RAM total — [oracle.com/cloud/free](https://www.oracle.com/cloud/free/)
- **HTTPS**: Let's Encrypt (gratis, otomatis via certbot)
- **Domain**: pakai domain/subdomain milik sekolah (mis. `absensi.sekolahxx.sch.id`)

Langkah:
1. Daftar Oracle Cloud (perlu kartu untuk verifikasi identitas; tidak ditagih pada tier Always Free).
2. Buat VM Ubuntu 22.04/24.04 (shape Always Free), catat **public IP**.
3. Arahkan **A record** domain sekolah → public IP (dari panel DNS sekolah).
4. Upload `dist/absensi-release-*.zip` + `deploy-server.sh` ke VM (`scp`).
5. Jalankan: `sudo bash deploy-server.sh /tmp/absensi-release-*.zip absensi.sekolahxx.sch.id`
6. HTTPS otomatis dari certbot (Let's Encrypt).

Batas Always Free yang perlu dipahami: 1/8 OCPU + 1 GB RAM per VM AMD.
Cukup untuk ±1.000 murid, tapi monitor pemakaian; bila kurang, pertimbangkan VPS berbayar murah.

## Jalur B — Free shared hosting (mulai tercepat, tanpa kartu)

Contoh penyedia (semua punya tier gratis): **InfinityFree**, **Byet.host** —
menyediakan PHP 8.x + MySQL + SSL subdomain gratis, tanpa iklan pada sebagian tier.
Lihat [byet.host/free-hosting](https://byet.host/free-hosting) (5 GB NVMe, PHP 8.3, MySQL 8,
SSL subdomain gratis) dan InfinityFree (mirip, unlimited traffic "fair use").

Langkah:
1. Daftar, dapatkan subdomain gratis, mis. `smkn20-absensi.infinityfreeapp.com`.
2. Dari panel:
   - Buat database MySQL; catat host/user/pass.
   - Aktifkan SSL (subdomain gratis / Let's Encrypt).
3. Upload isi `dist/absensi-release-*.zip` ke folder di atas `htdocs`
   (letakkan `public/*` ke `htdocs` — sesuaikan; bila panel memaksa semua masuk
   `htdocs`, pindahkan isi `public/` ke root dan sesuaikan `index.php`).
4. Salin `.env.production.example` → `.env`, isi kredensial DB dari panel
   (`APP_ENV=production`, `APP_URL=https://smkn20-absensi.infinityfreeapp.com`).
5. Jalankan migrasi dari panel bila tersedia `artisan`/SSH, atau buatkan endpoint
   sementara untuk menjalankan `php artisan migrate --force` sekali.
6. Cron: sebagian free host menyediakan "Cron Jobs" sederhana (frekuensi terbatas).
   Bila cron per menit tidak tersedia, aktifkan mark-absent dari akses pertama
   tiap pagi (on-demand trigger) — bisa ditambahkan nanti.

Kelemahan yang perlu diketahui: kuota CPU/inode kecil, kadang ada "activity
verification", dan lambat pada jam puncak. Cukup untuk uji coba & sekolah kecil.

## Jalur C — Server komputer sekolah (LAN, tanpa internet)

Untuk scanner terminal di gerbang/di kelas yang hanya butuh jaringan lokal:
1. Satu PC sekolah jadi server: install Laragon/XAMPP, restore database.
2. PC diberi IP statis LAN, mis. `192.168.1.10`.
3. Akses dari HP guru/murid lewat Wi-Fi sekolah: `http://192.168.1.10`.
4. Catatan: **kamera QR** butuh secure context — di HTTP, hanya `localhost` yang dianggap
   aman. Untuk LAN, gunakan **scanner unggah gambar** (`/scanner` menu "pindai gambar QR")
   atau pasang sertifikat self-signed + percayai di perangkat (lebih rumit).

---

## Domain gratis? (opsional)

Domain gratis memang ada (mis. `.tk/.ml/.ga` via Freenom) tetapi registrasinya sering
bermasalah dan kredibilitasnya rendah untuk sekolah. Lebih baik:
- **Pakai domain `sch.id` milik sekolah** (biasanya sudah dimiliki Pemda/Dinas) → subdomain
  `absensi.<namasekolah>.sch.id`.
- Atau cukup pakai **subdomain gratis dari free host** (Jalur B).

---

## Yang tetap gratis & otomatis di semua jalur

- **HTTPS**: Let's Encrypt (Jalur A/B) — sertifikat gratis, perpanjangan otomatis via cron certbot.
- **PWA install ke HP**: bekerja setelah HTTPS aktif — tidak ada biaya.
- **Aplikasi**: kode milik sekolah, tanpa lisensi berbayar (stack open source:
  Laravel, Tailwind, DomPDF, PhpSpreadsheet, html5-qrcode).

## Saran konkret untuk SMKN 20

1. **Sekarang (hari ini)**: Jalur B — daftar free hosting, subdomain gratis, langsung online untuk uji coba guru & murid terbatas.
2. **Bulan depan**: Jalur A — Oracle Always Free + subdomain `sch.id` sekolah, migrasi data dari jalur B.
3. **Jangka panjang**: bila ingin lebih stabil, VPS murah (±Rp 50–100 ribu/bulan) di penyedia lokal (Vultr/DigitalOcean/lokasi Jakarta) dengan skrip `deploy-server.sh` yang sama.

Semua jalur memakai **paket rilis** (`dist/absensi-release-*.zip`) dan
**skrip deploy** (`deploy-server.sh`) yang sama — jadi berpindah jalur tidak
butuh pengulangan kerja.
