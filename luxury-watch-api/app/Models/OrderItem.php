<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model 
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'qty',
        'unit_price',
        'total_price'
    ];

    // Add this relationship
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Add this relationship
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}