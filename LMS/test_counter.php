<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$jenisSurat = App\Models\JenisSurat::first();

if ($jenisSurat) {
    echo "Current counter: " . $jenisSurat->getCurrentCounter() . PHP_EOL;
    echo "Next counter preview: " . $jenisSurat->peekNextCounter() . PHP_EOL;
    echo "Counter month: " . $jenisSurat->last_reset_month . PHP_EOL;
    echo "Current month: " . \Carbon\Carbon::now()->format('Y-m') . PHP_EOL;
} else {
    echo "No jenis surat found" . PHP_EOL;
}
