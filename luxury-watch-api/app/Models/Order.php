<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model 
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'total',
        'subtotal',
        'shipping_fee',
        'currency',
        'status',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'delivery_notes',
        'payment_method',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}