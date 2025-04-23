<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test';
    protected $description = 'Test database connection';

    public function handle()
    {
        try {
            $result = DB::connection()->getPdo();
            $this->info("Connected successfully to: " . DB::connection()->getDatabaseName());
            return 0;
        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
            return 1;
        }
    }
}
