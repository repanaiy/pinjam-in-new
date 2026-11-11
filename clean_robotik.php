<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = App\Models\Item::where('name','like','%kit robotik%')->orWhere('name','like','%robotik%')->get();
foreach($items as $i){ echo $i->id.'|'.$i->name.'|'.$i->code.'|'.$i->category_id.'|'.$i->description."\n"; }
echo "JUMLAH: ".count($items)."\n";

$deleted = App\Models\Item::where('name','like','%kit robotik%')->orWhere('name','like','%robotik%')->delete();
echo "DELETED: $deleted\n";