<?php
// app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\MidtransService;


class OrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan milik user yang sedang login.
     */
    public function index()
    {
        // PENTING: Jangan gunakan Order::all() !
        // Kita hanya mengambil order milik user yg sedang login menggunakan relasi hasMany.
        // auth()->user()->orders() akan otomatis memfilter: WHERE user_id = current_user_id
        $orders = auth()->user()->orders()
            ->with(['items.product']) // Eager Load nested: Order -> OrderItems -> Product
            ->latest() // Urutkan dari pesanan terbaru
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Menampilkan detail satu pesanan.
     */
    public function show(Order $order, MidtransService $midtrans)
{
    // Security
    if ($order->user_id !== auth()->id()) {
        abort(403);
    }

    // Load relasi
    $order->load(['items.product', 'items.product.primaryImage']);

    // 🔥 BUAT SNAP TOKEN JIKA BELUM ADA
    if ($order->payment_status === 'unpaid' && !$order->snap_token) {
        $snapToken = $midtrans->createSnapToken($order);

        $order->update([
            'snap_token' => $snapToken
        ]);
    }

    return view('orders.show', compact('order'));
}

}