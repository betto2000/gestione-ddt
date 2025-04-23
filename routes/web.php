<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/api.php';


Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
