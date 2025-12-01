<?php
// File: routes/api.php
// IMPORTANT: This file should be in routes/api.php NOT routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes here automatically get /api prefix
| Example: Route::get('/products') becomes /api/products
*/

// ------------------- Root API Index -------------------
// Shows all available endpoints
Route::get('/', [ApiController::class, 'index']);

// ------------------- Authentication (Public) -------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

// Alternative auth routes (backward compatibility)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// ------------------- Password Reset (Public) -------------------
Route::prefix('password')->group(function () {
    Route::post('/email', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset', [PasswordResetController::class, 'reset']);
});

// ------------------- Products (Public) -------------------
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/brand/{brand}', [ProductController::class, 'productsByBrand']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

// Alternative product routes (backward compatibility)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/brand/{brand}', [ProductController::class, 'productsByBrand']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// ------------------- Protected Routes (Auth Required) -------------------
Route::middleware('auth:sanctum')->group(function () {
    
    // User Profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Shopping Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'add']);
        Route::patch('/items/{id}', [CartController::class, 'update']);
        Route::put('/items/{id}', [CartController::class, 'update']); // Alternative
        Route::delete('/items/{id}', [CartController::class, 'remove']);
    });

    // Alternative cart routes (backward compatibility)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'add']);
    Route::patch('/cart/items/{id}', [CartController::class, 'update']);
    Route::delete('/cart/items/{id}', [CartController::class, 'remove']);

    // Orders & Checkout
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });

    // Alternative order routes (backward compatibility)
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

