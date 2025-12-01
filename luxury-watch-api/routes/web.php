<?php
// File: routes/web.php

use Illuminate\Support\Facades\Route;

// Return API documentation at root
Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Luxury Watch E-Commerce API',
        'version' => '1.0',
        'documentation' => url('/api'),
        'status' => 'active',
        'endpoints' => [
            'API Base' => url('/api'),
            'Documentation' => url('/api'),
        ]
    ]);
});