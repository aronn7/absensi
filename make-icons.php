<?php
// Membuat ikon PWA (192, 512, maskable-512): kotak teal + topi wisuda putih di tengah.
// Jalankan: php make-icons.php

$dir = __DIR__ . '/public/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$targets = [
    ['icon-192.png', 192, false],
    ['icon-512.png', 512, false],
    ['maskable-512.png', 512, true],
];

foreach ($targets as [$name, $size, $maskable]) {
    $im = imagecreatetruecolor($size, $size);
    $teal = imagecolorallocate($im, 8, 123, 104);
    $white = imagecolorallocate($im, 255, 255, 255);

    imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, $teal);
    $pad = $maskable ? (int) ($size * 0.30) : (int) ($size * 0.24);

    $cx = (int) ($size / 2);
    $capTop = $pad;
    $capBot = (int) ($size / 2);
    $capHalf = (int) ($size / 2 - $pad);

    // topi: belah ketupat terisi
    $cap = [
        $cx, $capTop,
        $cx + $capHalf, $capBot,
        $cx, $capBot + (int) ($capHalf * 0.55),
        $cx - $capHalf, $capBot,
    ];
    imagefilledpolygon($im, $cap, $white);

    // dasar topi (bentuk U)
    imagesetthickness($im, max(3, (int) ($size / 26)));
    $bw = (int) ($capHalf * 0.7);
    $top = (int) ($capBot + $capHalf * 0.28);
    $bot = (int) ($capBot + $capHalf * 0.78);
    imageline($im, $cx - $bw, $top, $cx - $bw, $bot, $white);
    imageline($im, $cx + $bw, $top, $cx + $bw, $bot, $white);
    imageline($im, $cx - $bw, $bot, $cx + $bw, $bot, $white);

    imagepng($im, "$dir/$name");
    imagedestroy($im);
    echo "ok: $name\n";
}
