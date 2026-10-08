<?php
// Verifikasi konfigurasi siap produksi: php check-production.php
// Keluar 0 bila semua lolos; 1 bila ada temuan.

$passes = [];
$failures = [];

$check = function (string $label, bool $ok, string $hint = '') use (&$passes, &$failures): void {
    if ($ok) {
        $passes[] = $label;
        echo "  OK   $label\n";
    } else {
        $failures[] = $label;
        echo "  FAIL $label" . ($hint !== '' ? " — $hint" : '') . "\n";
    }
};

echo "Cek kesiapan produksi\n=====================\n";

$env = is_file(__DIR__ . '/.env') ? file_get_contents(__DIR__ . '/.env') : '';
$check('berkas .env ada', $env !== '');
if ($env === '') {
    exit(1);
}

$envVal = function (string $key) use ($env): ?string {
    if (preg_match("/^{$key}=(.*)$/m", $env, $m)) {
        return trim($m[1], "\"' ");
    }
    return null;
};

$check('APP_ENV=production', $envVal('APP_ENV') === 'production', 'saat ini ' . ($envVal('APP_ENV') ?? '(kosong)'));
$check('APP_DEBUG=false', strcasecmp((string) $envVal('APP_DEBUG'), 'false') === 0);
$check('APP_KEY terisi', (bool) $envVal('APP_KEY'));
$appUrl = (string) $envVal('APP_URL');
$check('APP_URL memakai https://', str_starts_with($appUrl, 'https://'), "saat ini $appUrl");
$check('SESSION_SECURE_COOKIE=true', strcasecmp((string) $envVal('SESSION_SECURE_COOKIE'), 'true') === 0);
$check('DB_PASSWORD terisi', (bool) $envVal('DB_PASSWORD'), 'kosong berbahaya di produksi');

$check('manifest.json ada', is_file(__DIR__ . '/public/manifest.json'));
$check('sw.js ada', is_file(__DIR__ . '/public/sw.js'));
$check('offline.html ada', is_file(__DIR__ . '/public/offline.html'));
$check('ikon PWA 192/512 ada', is_file(__DIR__ . '/public/icons/icon-192.png') && is_file(__DIR__ . '/public/icons/icon-512.png'));
$check('aset Vite ter-build', is_dir(__DIR__ . '/public/build'));

foreach (['app', 'guest'] as $layout) {
    $src = @file_get_contents(__DIR__ . "/resources/views/layouts/{$layout}.blade.php") ?: '';
    $check("layout $layout memuat manifest", str_contains($src, 'manifest.json'));
    $check("layout $layout memuat registrasi service worker", str_contains($src, 'serviceWorker'));
}

echo "\nHasil: " . count($passes) . ' lolos, ' . count($failures) . ' temuan' . (count($failures) !== 0 ? " — perbaiki sebelum rilis.\n" : " — siap produksi.\n");
exit(count($failures) !== 0 ? 1 : 0);
