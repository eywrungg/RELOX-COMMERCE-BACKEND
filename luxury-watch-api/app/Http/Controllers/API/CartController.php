<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
   
    /**
     * GET /api/cart
     * View current user's cart
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = Cart::with('items.product')->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name ?? 'Unknown',
                    'qty' => $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) ($item->qty * $item->unit_price),
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'brand' => $item->product->brand,
                        'image' => url($item->product->image), // ✅ FIXED: Full URL
                        'stock' => $item->product->stock,
                    ] : null,
                ];
            }),
            'total' => (float) $cart->items->sum(fn($i) => $i->qty * $i->unit_price),
            'item_count' => $cart->items->sum('qty'),
        ]);
    }

    /**
     * POST /api/cart/items
     * Add item to cart
     */
    public function add(Request $request)
    {
        // Add debug logging
        \Log::info('Add to cart attempt', [
            'user_id' => $request->user() ? $request->user()->id : 'NOT AUTHENTICATED',
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        
        if (!$user) {
            \Log::error('User not authenticated in add to cart');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        \Log::info('Cart found/created', ['cart_id' => $cart->id]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['qty']) {
            return response()->json([
                'message' => 'Insufficient stock',
                'available' => $product->stock
            ], 422);
        }

        // Check if item already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQty = $cartItem->qty + $validated['qty'];
            if ($newQty > $product->stock) {
                return response()->json([
                    'message' => 'Insufficient stock',
                    'available' => $product->stock,
                    'current_in_cart' => $cartItem->qty
                ], 422);
            }
            $cartItem->qty = $newQty;
            $cartItem->save();
            $message = 'Cart item quantity updated';
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'qty' => $validated['qty'],
                'unit_price' => $product->price
            ]);
            $message = 'Item added to cart';
        }

        \Log::info('Cart item created/updated', ['cart_item_id' => $cartItem->id]);

        // Load product relationship for response
        $cartItem->load('product');

        return response()->json([
            'message' => $message,
            'cart_item' => [
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'qty' => $cartItem->qty,
                'unit_price' => (float) $cartItem->unit_price,
                'total_price' => (float) ($cartItem->qty * $cartItem->unit_price),
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'brand' => $cartItem->product->brand,
                    'image' => url($cartItem->product->image), // ✅ Full URL
                    'stock' => $cartItem->product->stock,
                ]
            ]
        ], 201);
    }

    /**
     * PATCH /api/cart/items/{id}
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'qty' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $id)
            ->firstOrFail();

        $product = Product::findOrFail($cartItem->product_id);

        if ($product->stock < $validated['qty']) {
            return response()->json([
                'message' => 'Insufficient stock',
                'available' => $product->stock
            ], 422);
        }

        $cartItem->qty = $validated['qty'];
        $cartItem->save();

        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => [
                'id' => $cartItem->id,
                'qty' => $cartItem->qty,
                'unit_price' => (float) $cartItem->unit_price,
                'total_price' => (float) ($cartItem->qty * $cartItem->unit_price),
            ]
        ]);
    }

    /**
     * DELETE /api/cart/items/{id}
     * Remove item from cart
     */
    public function remove(Request $request, $id)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $id)
            ->firstOrFail();

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }
}