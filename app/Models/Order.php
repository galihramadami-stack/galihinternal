<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

   protected $fillable = [
    'user_id',
    'order_number',
    'total_amount',
    'shipping_cost',
    'status',
    'payment_status',
    'snap_token', // <--- Tambahkan baris ini!
    'shipping_name',
    'shipping_phone',
    'shipping_address',
];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}