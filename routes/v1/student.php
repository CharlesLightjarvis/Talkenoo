<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    return "Hello World";
});