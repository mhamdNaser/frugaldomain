<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$variantIds = DB::table('inventory_levels')->pluck('product_variant_id')->filter()->unique();

$stats = DB::table('files')
    ->select('fileable_type','role', DB::raw('COUNT(*) as c'))
    ->whereIn('fileable_id', $variantIds)
    ->groupBy('fileable_type','role')
    ->orderByDesc('c')
    ->get();

foreach ($stats as $s) {
    echo "{$s->fileable_type} | role=" . ($s->role ?? 'null') . " | {$s->c}\n";
}

echo "\nInventory variants with no variant files:\n";
$missing = DB::table('inventory_levels as il')
    ->leftJoin('files as f', function($j){
        $j->on('f.fileable_id','=','il.product_variant_id')
          ->where('f.fileable_type','=','App\\Modules\\Catalog\\Models\\ProductVariant')
          ->whereNull('f.deleted_at');
    })
    ->whereNull('f.id')
    ->select('il.id','il.product_variant_id')
    ->limit(20)
    ->get();
foreach($missing as $m){
    echo "inventory {$m->id} variant {$m->product_variant_id}\n";
}
