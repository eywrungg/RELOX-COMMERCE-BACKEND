<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Checkout - Place order from cart
     * POST /api/checkout
     */
    public function checkout(Request $request)
    {
        // Validate delivery information
        $validator = Validator::make($request->all(), [
            'delivery_name' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'delivery_city' => 'required|string|max:100',
            'delivery_postal_code' => 'required|string|max:20',
            'delivery_notes' => 'nullable|string',
            'payment_method' => 'required|string|in:cod,card,gcash,paymaya'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 422);
        }

        // Check stock
        foreach ($cart->items as $it) {
            if (!$it->product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in cart'
                ], 422);
            }
            
            if ($it->qty > $it->product->stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for ' . $it->product->name
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = $cart->items->sum(fn($i) => $i->qty * $i->unit_price);
            $shippingFee = 0;
            $total = $subtotal + $shippingFee;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'currency' => 'PHP',
                'status' => 'pending',
                'delivery_name' => $request->delivery_name,
                'delivery_phone' => $request->delivery_phone,
                'delivery_address' => $request->delivery_address,
                'delivery_city' => $request->delivery_city,
                'delivery_postal_code' => $request->delivery_postal_code,
                'delivery_notes' => $request->delivery_notes,
                'payment_method' => $request->payment_method,
            ]);

            // Create order items
            foreach ($cart->items as $it) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $it->product_id,
                    'name' => $it->product->name,
                    'qty' => $it->qty,
                    'unit_price' => $it->unit_price,
                    'total_price' => $it->qty * $it->unit_price
                ]);

                // Decrement stock
                $it->product->decrement('stock', $it->qty);
            }

            // Clear cart
            $cart->items()->delete();
            $cart->update(['total' => 0]);

            DB::commit();

            // Load order with items
            $order->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all orders
     * GET /api/orders
     */
    public function index(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ], 200);
    }

    /**
     * Get single order
     * GET /api/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = Order::with('items.product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ], 200);
    }
}