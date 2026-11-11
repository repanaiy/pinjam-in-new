<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = App\Models\Item::all();
foreach($items as $i){ echo $i->id.'|'.$i->name.'|'.$i->code."\n"; }
echo "TOTAL: ".count($items)."\n";