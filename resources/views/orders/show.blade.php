{{-- resources/views/orders/show.blade.php --}}

@extends('layouts.app') {{-- Pastikan layout ini sudah memuat Bootstrap CSS & JS --}}

@section('title', 'Detail Pesanan')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            {{-- Tombol Kembali --}}
            <div class="mb-4">
                <a href="{{ route('orders.index') }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pesanan Saya
                </a>
            </div>

            <div class="card shadow-sm border-0">
                {{-- Header Order --}}
                <div class="card-header bg-info py-4 border-bottom">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h1 class="h3 fw-bold mb-1">Order #{{ $order->order_number }}</h1>
                            <p class="text-muted mb-0">
                                <i class="bi bi-calendar3"></i> {{ $order->created_at->format('d M Y, H:i') }}
                            </p>
                        </div>

                        {{-- Status Badge --}}
                        <div class="mt-3 mt-md-0">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-warning text-dark',
                                    'processing' => 'bg-info text-white',
                                    'shipped' => 'bg-primary text-white',
                                    'delivered' => 'bg-success text-white',
                                    'cancelled' => 'bg-danger text-white',
                                ];
                                $badgeClass = $statusClasses[$order->status] ?? 'bg-warning text-white';
                            @endphp
                            <span class="badge rounded-pill px-4 py-2 fs-6 {{ $badgeClass }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    {{-- Detail Items --}}
                    <div class="p-4 p-md-5">
                        <h5 class="fw-bold mb-4">Produk yang Dipesan</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="py-3">Produk</th>
                                        <th scope="col" class="text-center py-3">Qty</th>
                                        <th scope="col" class="text-end py-3">Harga</th>
                                        <th scope="col" class="text-end py-3">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td class="py-3">
                                            <span class="fw-medium text-dark">{{ $item->product_name }}</span>
                                        </td>
                                        <td class="text-center py-3">{{ $item->quantity }}</td>
                                        <td class="text-end py-3 text-muted">
                                            Rp {{ number_format($item->price, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end py-3 fw-bold">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-top-0">
                                    @if($order->shipping_cost > 0)
                                    <tr>
                                        <td colspan="3" class="text-end pt-4 border-0">Ongkos Kirim:</td>
                                        <td class="text-end pt-4 border-0">
                                            Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td colspan="3" class="text-end pt-2 border-0 fw-bold fs-5">TOTAL BAYAR:</td>
                                        <td class="text-end pt-2 border-0 fw-bold fs-5 text-primary">
                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Alamat Pengiriman --}}
                    <div class="p-4 p-md-5 bg-light border-top border-bottom">
                        <h5 class="fw-bold mb-3">Alamat Pengiriman</h5>
                        <div class="card border-0 shadow-none bg-transparent">
                            <div class="card-body p-0">
                                <p class="fw-bold mb-1">{{ $order->shipping_name }}</p>
                                <p class="text-muted mb-1"><i class="bi bi-telephone"></i> {{ $order->shipping_phone }}</p>
                                <p class="text-muted mb-0"><i class="bi bi-geo-alt"></i> {{ $order->shipping_address }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Bayar (hanya tampil jika pending) --}}
                @if(isset($snapToken) && $order->status === 'pending')
                <div class="card-footer bg-white py-5 text-center">
                    <p class="text-muted mb-4">
                        Selesaikan pembayaran Anda sebelum batas waktu berakhir.
                    </p>
                    <button id="pay-button" class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-3 fw-bold">
                        💳 Bayar Sekarang
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(isset($snapToken))
    @push('scripts')
        {{-- Load Snap JS dari Midtrans --}}
        <script src="{{ config('midtrans.snap_url') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const payButton = document.getElementById('pay-button');

                if (payButton) {
                    payButton.addEventListener('click', function() {
                        payButton.disabled = true;
                        payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';

                        window.snap.pay('{{ $snapToken }}', {
                            onSuccess: function(result) {
                                window.location.href = '{{ route("orders.success", $order) }}';
                            },
                            onPending: function(result) {
                                window.location.href = '{{ route("orders.pending", $order) }}';
                            },
                            onError: function(result) {
                                alert('Pembayaran gagal! Silakan coba lagi.');
                                payButton.disabled = false;
                                payButton.innerHTML = '💳 Bayar Sekarang';
                            },
                            onClose: function() {
                                payButton.disabled = false;
                                payButton.innerHTML = '💳 Bayar Sekarang';
                            }
                        });
                    });
                }
            });
        </script>
    @endpush
@endif
@endsection