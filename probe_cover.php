<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$i = App\Models\Item::find(4);
echo 'COVER: ';
echo $i->cover()->first()->path ?? 'tidak ada cover';
echo PHP_EOL;
foreach($i->photos()->get() as $x){
    echo $x->path,'|',$x->is_cover,'|',$x->sort_order,"\n";
}
