<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        \Illuminate\Support\Facades\Route::aliasMiddleware('device.token', \App\Http\Middleware\CheckDeviceToken::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::aliasMiddleware('device.token', \App\Http\Middleware\CheckDeviceToken::class);

        // Formatta i timestamp per SQL Server
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::listen(function (QueryExecuted $query) {
                $query->sql = str_replace("'Y-m-d H:i:s.u'", "'Y-m-d H:i:s'", $query->sql);
            });
        }
    }
}
