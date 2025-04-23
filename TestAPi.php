<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
    echo "Tabelle disponibili:\n";
    foreach ($tables as $table) {
        echo "- " . $table->TABLE_NAME . "\n";
    }

    // Tenta di contare i record di alcune varianti del nome della tabella
    $variants = [
        'MA_SaleDocDetail',
        'MA_SaleDocDetails',
        'dbo.MA_SaleDocDetail',
        'dbo.MA_SaleDocDetails',
        'MA_SALEDOCDETAIL',
        'MA_SALEDOCDETAILS'
    ];

    foreach ($variants as $tableName) {
        try {
            $count = DB::table($tableName)->count();
            echo "Tabella $tableName esiste con $count record\n";
        } catch (\Exception $e) {
            echo "Tabella $tableName: " . $e->getMessage() . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
