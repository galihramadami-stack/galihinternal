@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">

                {{-- HEADER ORDER --}}
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="fw-bold mb-1">
                                Order #{{ $order->order_number }}
                            </h4>
                            <small class="text-muted">
                                {{ $order->created_at->format('d M Y, H:i') }}
                            </small>
                        </div>

                        @php
                        $statusClass = match($order->status) {
                        'pending' => 'warning',
                        'processing' => 'primary',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                        };
                        @endphp

                        <span class="badge bg-{{ $statusClass }} px-3 py-2 text-uppercase">
                            {{ $order->status }}
                        </span>
                    </div>
                </div>

                {{-- PRODUK --}}
                <div class="card-body">
                    <h5 class="fw-semibold mb-4">Produk yang Dipesan</h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">
                                        Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-top">
                                @if($order->shipping_cost > 0)
                                <tr>
                                    <td colspan="3" class="text-end pt-3">
                                        Ongkos Kirim
                                    </td>
                                    <td class="text-end pt-3">
                                        Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="text-end fs-5 fw-bold">
                                        TOTAL BAYAR
                                    </td>
                                    <td class="text-end fs-5 fw-bold text-primary">
                                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ALAMAT --}}
                <div class="card-body bg-light border-top">
                    <h5 class="fw-semibold mb-3">Alamat Pengiriman</h5>
                    <p class="mb-1 fw-semibold">{{ $order->shipping_name }}</p>
                    <p class="mb-1">{{ $order->shipping_phone }}</p>
                    <p class="mb-0">{{ $order->shipping_address }}</p>
                </div>

                {{-- BAYAR --}}
              @if($order->payment_status === 'unpaid' && $order->snap_token)
            <div class="card-footer bg-white text-center">
                <p class="text-muted mb-3">
                    Selesaikan pembayaran Anda sebelum batas waktu berakhir.
                </p>
                <button id="pay-button" class="btn btn-primary btn-lg px-5 shadow">
                    💳 Bayar Sekarang
                </button>
            </div>
            @endif
            </div>

        </div>
    </div>
</div>
@endsection

{{-- MIDTRANS --}}
@push('scripts')
@if(!empty($order->snap_token))
<script src="{{ config('midtrans.snap_url') }}" data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const payButton = document.getElementById('pay-button');
    if (!payButton) return;

    payButton.addEventListener('click', function () {
        payButton.disabled = true;
        payButton.innerText = 'Memproses...';

        snap.pay('{{ $order->snap_token }}', {
            onSuccess() {
                window.location.href = '{{ route("orders.success", $order) }}';
            },
            onPending() {
                window.location.href = '{{ route("orders.pending", $order) }}';
            },
            onError() {
                alert('Pembayaran gagal. Silakan coba lagi.');
                payButton.disabled = false;
                payButton.innerText = '💳 Bayar Sekarang';
            },
            onClose() {
                payButton.disabled = false;
                payButton.innerText = '💳 Bayar Sekarang';
            }
        });
    });
});
</script>
@endif
@endpush