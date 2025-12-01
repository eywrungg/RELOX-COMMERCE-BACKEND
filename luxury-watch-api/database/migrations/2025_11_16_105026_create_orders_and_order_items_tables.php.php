<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Pricing
            $table->decimal('total', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->string('currency', 10)->default('PHP');
            
            // Status
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');
            
            // Delivery Information
            $table->string('delivery_name');
            $table->string('delivery_phone', 20);
            $table->text('delivery_address');
            $table->string('delivery_city', 100);
            $table->string('delivery_postal_code', 20);
            $table->text('delivery_notes')->nullable();
            
            // Payment
            $table->string('payment_method', 50);
            
            // Meta
            $table->json('meta')->nullable();
            
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('qty')->unsigned();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};