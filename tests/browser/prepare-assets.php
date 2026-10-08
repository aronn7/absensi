<?php
// Saya menyiapkan aset untuk uji E2E scanner kamera:
//  1) mengekstrak frame QR dari video sintetis .qa/demo-camera.y4m menjadi PNG
//  2) mem-bundle build ESM html5-qrcode menjadi satu file yang bisa diimpor browser
// Jalankan: php tests/browser/prepare-assets.php

$root = dirname(__DIR__, 2);
$outDir = $root . '/public/vendor-tmp';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

// --- 1. Frame PNG dari y4m ---
$y4m = $root . '/.qa/demo-camera.y4m';
$png = $outDir . '/frame1.png';
if (file_exists($y4m)) {
    $f = file_get_contents($y4m);
    preg_match('/YUV4MPEG2 W(\d+) H(\d+)/', substr($f, 0, 60), $m);
    $w = (int) $m[1];
    $h = (int) $m[2];
    $pos = strpos($f, 'FRAME') + 5 + 1; // "FRAME\n"
    $y = substr($f, $pos, $w * $h);
    $im = imagecreatetruecolor($w, $h);
    for ($py = 0; $py < $h; $py++) {
        for ($px = 0; $px < $w; $px++) {
            $val = ord($y[$py * $w + $px]);
            imagesetpixel($im, $px, $py, imagecolorallocate($im, $val, $val, $val));
        }
    }
    imagepng($im, $png);
    imagedestroy($im);
    echo "frame: $png\n";
} else {
    echo "skip frame (y4m tidak ada)\n";
}

// --- 2. Bundle ESM html5-qrcode ---
$esmRoot = $root . '/node_modules/html5-qrcode/esm/';
$visited = [];

function bundle($root, $rel, &$visited)
{
    if (isset($visited[$rel]))
        return '';
    $visited[$rel] = true;
    $src = file_get_contents($root . $rel);
    if ($src === false)
        throw new Exception("missing $rel");
    $dir = dirname($rel) === '.' ? '' : dirname($rel) . '/';
    preg_match_all('/^import\s.*?[\x27"]([^\x27"]+)[\x27"];?/m', $src, $m);
    $out = '';
    foreach ($m[1] as $d) {
        $child = $dir . preg_replace('#^\./#', '', $d) . (str_ends_with($d, '.js') ? '' : '.js');
        $out .= bundle($root, $child, $visited);
    }
    // Saya menghapus import/export supaya modul jadi satu berkas mandiri.
    $src = preg_replace('/^import\s.*?[\x27"][^\x27"]*[\x27"];?\s*$/m', '', $src);
    $src = preg_replace('/^export\s\{.*?\};?\s*$/m', '', $src);
    $src = preg_replace('/^export\s(?=var|function|class|const|let)/m', '', $src);
    return $out . "/* $rel */\n" . $src . "\n";
}

$bundle = bundle($esmRoot, 'html5-qrcode.js', $visited);
$bundle .= "export { Html5Qrcode };\n";
file_put_contents($outDir . '/html5-qrcode.esm.js', $bundle);
echo "bundle: " . count($visited) . " modul\n";
