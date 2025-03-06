<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/v1/chat.php';
    require __DIR__ . '/v1/admin.php';
    require __DIR__ . '/v1/auth.php';
});
