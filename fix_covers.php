<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$map = [
    1 => 'kamera dslr.jpg',       // ELK-012 Kamera DSLR
    2 => 'proyektor epson.jpg',   // AVS-004 Proyektor
    3 => 'mikroskop.jpg',         // LAB-007 Mikroskop
    4 => 'tripod.jpg',            // AVS-009 Tripod
    5 => 'speaker portabel.jpg',  // AVS-015 Speaker
    7 => 'bola basket molten.jpg',// OLR-001 Bola Basket
];

foreach($map as $itemId => $filename){
    $item = App\Models\Item::find($itemId);
    if(!$item){ echo "ITEM $itemId NOT FOUND\n"; continue; }
    
    $src = public_path('images/'.$filename);
    if(!file_exists($src)){ echo "SRC MISSING: $src\n"; continue; }
    
    // hapus foto lama
    $item->photos()->delete();
    
    // copy ke storage
    $destDir = storage_path('app/public/items/'.$itemId);
    if(!is_dir($destDir)) mkdir($destDir, 0755, true);
    $dest = $destDir.'/cover.jpg';
    copy($src, $dest);
    
    // simpan di db
    \App\Models\ItemPhoto::create([
        'item_id' => $itemId,
        'path' => 'items/'.$itemId.'/cover.jpg',
        'is_cover' => true,
        'sort_order' => 1,
    ]);
    
    echo "OK: {$item->name} -> $filename\n";
}