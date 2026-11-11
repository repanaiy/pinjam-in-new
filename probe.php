<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$i = App\Models\Item::find(1);
echo 'COVER URL: '.$i->coverUrl()."\n";
echo 'NAMA: '.$i->name."\n";
echo 'GROUP BY NAME / CUPPING';
echo "\n";
$i = App\Models\Item::find(4);
echo 'NAMA1: '.$i->name."\n";
echo 'CODE1: '.$i->code."\n";
echo 'DESC1: '.$i->description."\n";
echo 'GRP1: '.print_r($i->category, true)."\n";
