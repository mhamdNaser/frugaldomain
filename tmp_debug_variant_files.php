<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('inventory_levels')->select('id','product_variant_id')->orderByDesc('id')->limit(8)->get();
$variantIds = $rows->pluck('product_variant_id')->filter()->unique()->values();

echo "Inventory variants:\n";
foreach ($rows as $r) {
    echo "inventory_level {$r->id} -> variant {$r->product_variant_id}\n";
}

echo "\nVariant files:\n";
$files = DB::table('files')
    ->select('id','fileable_type','fileable_id','role','type','url','path','altText')
    ->whereIn('fileable_id', $variantIds)
    ->orderByDesc('id')
    ->limit(50)
    ->get();

foreach ($files as $f) {
    echo "file {$f->id} | {$f->fileable_type}#{$f->fileable_id} | role={$f->role} | type={$f->type} | url=" . ($f->url ?? 'null') . " | path=" . ($f->path ?? 'null') . "\n";
}
