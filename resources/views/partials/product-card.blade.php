{{-- ================================================================
     FILE: resources/views/partials/product-card.blade.php
     ================================================================

     KOMPONEN REUSABLE untuk menampilkan kartu produk
     Dipanggil dengan: @include('partials.product-card', ['product' => $p])

     ================================================================ --}}

<div class="card product-card h-100 border-0 shadow-sm">
{{-- ↑ MULTIPLE CSS CLASSES:
     - card: Bootstrap card component
     - product-card: Custom class kita (bisa di-style di CSS)
     - h-100: Height 100% (semua card sama tinggi di grid)
     - border-0: Hilangkan border
     - shadow-sm: Bayangan kecil --}}

    <div class="position-relative">
    {{-- ↑ Kontainer untuk gambar + badge
         position-relative agar child bisa position-absolute --}}

        <a href="{{ route('catalog.show', $product->slug) }}">
        {{-- ↑ Link ke halaman detail produk
             $product->slug misalnya "laptop-gaming-asus"
             Hasil URL: /products/laptop-gaming-asus --}}

            <img src="{{ $product->image_url }}"
                 class="card-img-top"
                 alt="{{ $product->name }}"
                 style="height: 200px; object-fit: cover;">
            {{-- ↑ object-fit: cover
                 Gambar akan di-crop agar pas di kotak 200px
                 tanpa distorsi rasio aspek --}}
        </a>

        @if($product->has_discount)
        {{-- ↑ DIRECTIVE KONDISIONAL @if

             $product->has_discount adalah ACCESSOR di Model:
             public function getHasDiscountAttribute() {
                 return $this->discount_price !== null
                     && $this->discount_price < $this->price;
             }

             Jika true, tampilkan badge diskon --}}

            <span class="badge-discount">
                -{{ $product->discount_percentage }}%
            </span>
            {{-- ↑ Badge di pojok kiri atas gambar
                 Karena parent position-relative dan ini position-absolute
                 (didefinisikan di CSS) --}}
        @endif
        {{-- ↑ Tutup @if --}}

        @auth
        {{-- ↑ DIRECTIVE @auth - Hanya tampil jika user sudah login

             SAMA DENGAN:
             @if(Auth::check())

             KEBALIKANNYA:
             @guest - hanya tampil jika belum login --}}

            <button onclick="toggleWishlist({{ $product->id }})"
        class="wishlist-btn-{{ $product->id }} btn btn-light btn-sm rounded-circle p-2 transition">
    <i class="bi {{ Auth::check() && Auth::user()->hasInWishlist($product) ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary' }} fs-5"></i>
</button>
        @endauth
    </div>

    <div class="card-body d-flex flex-column">
    {{-- ↑ flex-column agar footer card bisa di-push ke bawah --}}

        <small class="text-muted mb-1">{{ $product->category->name }}</small>
        {{-- ↑ Mengakses RELASI
             $product->category = object Category (dari belongsTo)
             ->name = nama kategori

             Ini bisa dilakukan karena di Controller sudah
             dilakukan Eager Loading: with(['category']) --}}

        <h6 class="card-title mb-2">
            <a href="{{ route('catalog.show', $product->slug) }}"
               class="text-decoration-none text-dark stretched-link">
               {{-- ↑ stretched-link: Membuat SELURUH card clickable
                    (Bootstrap feature) --}}

                {{ Str::limit($product->name, 40) }}
                {{-- ↑ Str::limit() - Potong string jika terlalu panjang
                     "Laptop Gaming ASUS ROG Strix..." -> max 40 karakter
                     Ditambah "..." jika dipotong --}}
            </a>
        </h6>

        <div class="mt-auto">
        {{-- ↑ mt-auto: Margin-top auto
             Flex item ini akan "push" ke bawah
             Membuat harga selalu di bawah card --}}

            @if($product->has_discount)
                <small class="text-muted text-decoration-line-through">
                    {{ $product->formatted_original_price }}
                </small>
                {{-- ↑ Harga asli dicoret --}}
            @endif

            <div class="fw-bold text-primary">
                {{ $product->formatted_price }}
                {{-- ↑ Accessor: return 'Rp ' . number_format(...) --}}
            </div>
        </div>

        @if($product->stock <= 5 && $product->stock > 0)
        {{-- ↑ Multiple conditions dengan && --}}
            <small class="text-warning mt-2">
                <i class="bi bi-exclamation-triangle"></i>
                Stok tinggal {{ $product->stock }}
            </small>
        @elseif($product->stock == 0)
        {{-- ↑ @elseif untuk kondisi alternatif --}}
            <small class="text-danger mt-2">
                <i class="bi bi-x-circle"></i> Stok Habis
            </small>
        @endif
        {{-- ↑ Kalau stok > 5, tidak tampilkan apapun --}}
    </div>

    <div class="card-footer bg-white border-0 pt-0">
        <form action="{{ route('cart.add') }}" method="POST">
        {{-- ↑ FORM HTML dengan method POST
             action = URL tujuan form
             method = HTTP method --}}

            @csrf
            {{-- ↑ DIRECTIVE @csrf - WAJIB untuk setiap form POST!

                 Menghasilkan:
                 <input type="hidden" name="_token" value="random_token_123">

                 Laravel akan validasi token ini
                 Jika tidak ada atau tidak cocok = 419 error

                 INI MENCEGAH CSRF ATTACK --}}

            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            {{-- ↑ Hidden input: data yang dikirim tapi tidak terlihat user --}}

            <button type="submit"
                    class="btn btn-primary btn-sm w-100"
                    @if($product->stock == 0) disabled @endif>
                    {{-- ↑ ATTRIBUTE KONDISIONAL
                         Jika stok 0, tambahkan attribute disabled
                         Hasil: <button disabled>
                         Button tidak bisa diklik --}}

                <i class="bi bi-cart-plus me-1"></i>
                @if($product->stock == 0)
                    Stok Habis
                @else
                    Tambah Keranjang
                @endif
            </button>
        </form>
    </div>
</div>