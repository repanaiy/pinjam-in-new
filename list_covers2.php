<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(range(1,6) as $id){
    $i = App\Models\Item::find($id);
    if(!$i){ echo $id."|tidak ada item\n"; continue; }
    echo $id."|".$i->name."|cover=".($i->coverUrl()?'ada':'-')."\n";
}
