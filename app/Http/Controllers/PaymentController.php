<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Mengambil Snap Token untuk order ini (API Endpoint).
     * Dipanggil via AJAX dari frontend saat user klik "Bayar".
     */
    public function getSnapToken(Order $order, MidtransService $midtransService)
    {
        // 1. Authorization: Pastikan user adalah pemilik order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // 2. Cek apakah order sudah dibayar
        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Pesanan sudah dibayar.'], 400);
        }

        try {
            // 3. Generate Snap Token dari Midtrans
            $snapToken = $midtransService->createSnapToken($order);

            // 4. Simpan token ke database untuk referensi
            $order->update(['snap_token' => $snapToken]);

            // 5. Kirim token ke frontend
            return response()->json(['token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Order $order)
    {
    // Cek apakah ini memang milik user yang login
    if ($order->user_id !== auth()->id()) {
        abort(403);
    }
    // UPDATE STATUS MANUAL (Sambil nunggu materi Webhook)
        if ($order->status === 'pending') {
            $order->update([
                'status' => 'processing',
                'payment_status' => 'paid'
            ]);
        }

    return redirect()->route('orders.show', $order)->with('success', 'Pembayaran berhasil diproses!');
    }

    public function pending(Order $order)
    {
        // Cek apakah ini memang milik user yang login
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return redirect()->route('orders.show', $order)->with('warning', 'Pembayaran masih dalam status pending.');
    }

    public function show(Order $order)
    {
        // Cek apakah ini memang milik user yang login
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('orders.show', compact('order'));
    }
}