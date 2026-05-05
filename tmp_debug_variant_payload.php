<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('product_variants')
    ->select('id','title','raw_payload')
    ->whereIn('id',[1,2,3,4,5])
    ->get();

foreach($rows as $r){
  $payload = json_decode($r->raw_payload,true);
  $img = $payload['image'] ?? null;
  $imgId = $payload['image_id'] ?? null;
  $imgSrc = is_array($img) ? ($img['src'] ?? ($img['url'] ?? null)) : null;
  echo "variant {$r->id} image_id=" . ($imgId ?? 'null') . " image_src=" . ($imgSrc ?? 'null') . "\n";
}
