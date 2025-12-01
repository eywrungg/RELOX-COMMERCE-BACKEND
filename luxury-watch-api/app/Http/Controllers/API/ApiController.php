<?php
// File: app/Http/Controllers/API/ApiController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Display all available API endpoints
     * GET /api
     */
    public function index()
    {
        $baseUrl = url('/api');

        return response()->json([
            'message' => 'Welcome to Luxury Watch E-Commerce API',
            'version' => '1.0',
            'endpoints' => [
                'authentication' => [
                    'register' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/register',
                        'auth_required' => false,
                        'body' => [
                            'name' => 'string|required',
                            'email' => 'string|email|required',
                            'password' => 'string|min:8|required',
                            'password_confirmation' => 'string|required',
                        ],
                    ],
                    'login' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/login',
                        'auth_required' => false,
                        'body' => [
                            'email' => 'string|email|required',
                            'password' => 'string|required',
                        ],
                    ],
                    'logout' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/logout',
                        'auth_required' => true,
                    ],
                    'me' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/me',
                        'auth_required' => true,
                    ],
                ],
                'products' => [
                    'list' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/products',
                        'params' => 'q, brand, price_min, price_max, sort, per_page, page',
                        'auth_required' => false,
                    ],
                    'featured' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/products/featured',
                        'params' => 'limit',
                        'auth_required' => false,
                    ],
                    'by_brand' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/products/brand/{brand}',
                        'auth_required' => false,
                    ],
                    'single' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/products/{id}',
                        'auth_required' => false,
                    ],
                ],
                'cart' => [
                    'view' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/cart',
                        'auth_required' => true,
                        'description' => 'Get current user cart with items',
                    ],
                    'add' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/cart/items',
                        'auth_required' => true,
                        'body' => [
                            'product_id' => 'integer|required',
                            'qty' => 'integer|required|min:1',
                        ],
                    ],
                    'update' => [
                        'method' => 'PATCH',
                        'url' => $baseUrl . '/cart/items/{id}',
                        'auth_required' => true,
                        'body' => [
                            'qty' => 'integer|required|min:1',
                        ],
                    ],
                    'remove' => [
                        'method' => 'DELETE',
                        'url' => $baseUrl . '/cart/items/{id}',
                        'auth_required' => true,
                    ],
                ],
                'orders' => [
                    'checkout' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/checkout',
                        'auth_required' => true,
                        'description' => 'Place order from cart items',
                        'body' => [
                            'delivery_name' => 'string|required|max:255',
                            'delivery_phone' => 'string|required|max:20',
                            'delivery_address' => 'string|required|max:500',
                            'delivery_city' => 'string|required|max:100',
                            'delivery_postal_code' => 'string|required|max:20',
                            'delivery_notes' => 'string|optional|max:1000',
                            'payment_method' => 'string|required|in:cod,card,gcash,paymaya',
                        ],
                    ],
                    'list' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/orders',
                        'auth_required' => true,
                        'description' => 'Get all orders for current user',
                    ],
                    'single' => [
                        'method' => 'GET',
                        'url' => $baseUrl . '/orders/{id}',
                        'auth_required' => true,
                        'description' => 'Get single order details with items',
                    ],
                ],
                'password_reset' => [
                    'request_reset' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/password/email',
                        'auth_required' => false,
                        'body' => [
                            'email' => 'string|email|required',
                        ],
                    ],
                    'reset' => [
                        'method' => 'POST',
                        'url' => $baseUrl . '/password/reset',
                        'auth_required' => false,
                        'body' => [
                            'token' => 'string|required',
                            'email' => 'string|email|required',
                            'password' => 'string|min:8|required',
                            'password_confirmation' => 'string|required',
                        ],
                    ],
                ],
            ],
        ], 200, [], JSON_PRETTY_PRINT);
    }
}