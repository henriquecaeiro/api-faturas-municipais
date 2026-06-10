<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Municipality Billing API Showcase',
        'documentation' => '/README.md',
    ]);
});
