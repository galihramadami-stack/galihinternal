@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="container py-5">
    <h3 class="mb-4 fw-bold">🛒 Checkout</h3>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($cart->items->isEmpty())
        <div class="alert alert-info">
            Keranjang kosong.
            <a href="{{ route('catalog.index') }}">Belanja sekarang</a>
        </div>
    @else
        <div class="row">
            {{-- Ringkasan Pesanan --}}
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Ringkasan Pesanan</h5>

                        <ul class="list-group mb-3">
                            @foreach($cart->items as $item)
                                <li class="list-group-item d-flex justify-content-between">
                                    {{ $item->product?->name ?? 'Produk dihapus' }}
                                    x {{ $item->quantity }}

                                    <span>
                                        Rp {{ number_format($item->product->price * $item->quantity) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        <p class="fw-bold mb-1">
                            Ongkir: Rp {{ number_format($shippingCost) }}
                        </p>

                        <p class="fw-bold mb-0">
                            Total: Rp {{ number_format($total) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form Checkout --}}
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Data Pengiriman</h5>

                        <form action="{{ route('checkout.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Nama Penerima</label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', auth()->user()->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No. HP</label>
                                <input type="text" name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', auth()->user()->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" rows="3"
                                    class="form-control @error('address') is-invalid @enderror"
                                    required>{{ old('address', auth()->user()->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                🛒 Bayar & Buat Pesanan
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection