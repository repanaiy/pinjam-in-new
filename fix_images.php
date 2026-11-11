<?php

$dir = "C:\Users\ASM1\Videos\pinjam-app";
$src = $dir . "\public\images\Cameras_Zenit_11.jpg";

if (!file_exists($src)) {
    echo "SRC MISSING: $src\n";
    exit(1);
}

echo "Source OK\n";

$photos = \Illuminate\Support\Facades\DB::table('item_photos')->get(['id', 'item_id', 'path', 'is_cover']);

foreach ($photos as $p) {
    $dest = $dir . "\storage\app\public\\" . $p->path;
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!file_exists($dest)) {
        copy($src, $dest);
        echo "Copied to: " . $p->path . "\n";
    }
}

echo "Done. " . count($photos) . " photos processed.\n";