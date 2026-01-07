<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Membuat Order baru dari Keranjang Belanja User
     */
    public function createOrder(User $user, array $shippingData): Order
    {
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            throw new \Exception('Keranjang belanja kosong.');
        }

        return DB::transaction(function () use ($user, $cart, $shippingData) {

            $subtotal = 0;
            $shippingCost = 20000;

            // A. VALIDASI STOK & HITUNG SUBTOTAL
            foreach ($cart->items as $item) {

                // Lock row product untuk mencegah race condition
                $product = $item->product()->lockForUpdate()->first();

                if ($item->quantity > $product->stock) {
                    throw new \Exception("Stok produk {$product->name} tidak mencukupi.");
                }

                $subtotal += $product->price * $item->quantity;
            }

            $totalAmount = $subtotal + $shippingCost;

            // B. BUAT HEADER ORDER
            $order = Order::create([
                'user_id'          => $user->id,
                'order_number'     => 'ORD-' . strtoupper(Str::random(10)),
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'shipping_name'    => $shippingData['name'],
                'shipping_address' => $shippingData['address'],
                'shipping_phone'   => $shippingData['phone'],
                'total_amount'     => $totalAmount,
                'shipping_cost'    => $shippingCost,
            ]);

            // C. PINDAHKAN CART ITEMS → ORDER ITEMS
            foreach ($cart->items as $item) {
                $product = $item->product;

                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name, // snapshot
                    'price'        => $product->price,
                    'quantity'     => $item->quantity,
                    'subtotal'     => $product->price * $item->quantity,
                ]);

                // D. KURANGI STOK (ATOMIC)
                $product->decrement('stock', $item->quantity);
            }

            // E. BERSIHKAN KERANJANG
            $cart->items()->delete();

            return $order;
        });
    }
}