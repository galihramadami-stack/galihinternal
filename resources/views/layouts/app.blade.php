{{-- ================================================================
     FILE: resources/views/layouts/app.blade.php
     ================================================================

     INI ADALAH MASTER LAYOUT / PARENT TEMPLATE

     KONSEP TEMPLATE INHERITANCE:
     ┌─────────────────────────────────────────────┐
     │              layouts/app.blade.php          │
     │   ┌─────────────────────────────────────┐   │
     │   │  Navbar (sama di semua halaman)     │   │
     │   ├─────────────────────────────────────┤   │
     │   │  @yield('content')                  │   │
     │   │  ← Konten berbeda tiap halaman      │   │
     │   ├─────────────────────────────────────┤   │
     │   │  Footer (sama di semua halaman)     │   │
     │   └─────────────────────────────────────┘   │
     └─────────────────────────────────────────────┘

     KEUNTUNGAN:
     - DRY (Dont Repeat Yourself)
     - Navbar/Footer hanya ditulis 1x
     - Update di 1 tempat = update semua halaman

     ================================================================ --}}

<!DOCTYPE html>
{{-- ↑ Deklarasi HTML5 --}}

<html lang="id">
{{-- ↑ lang="id" penting untuk:
       - Screen reader (aksesibilitas)
       - SEO (search engine tahu bahasa halaman)
       - Auto-translate browser --}}

<head>
    <meta charset="UTF-8">
    {{-- ↑ Encoding karakter UTF-8
           Mendukung karakter Indonesia, emoji, dll --}}

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- ↑ VIEWPORT META - SANGAT PENTING untuk responsive!
           width=device-width: lebar sesuai layar device
           initial-scale=1.0: zoom level awal 100%
           Tanpa ini, website terlihat kecil di HP --}}

    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- ↑ CSRF TOKEN untuk request AJAX

         CSRF (Cross-Site Request Forgery) adalah serangan dimana
         hacker mengirim request dari website lain menggunakan
         session user yang masih aktif.

         CONTOH SERANGAN:
         User login di tokoonline.com
         User buka malicious-site.com
         Malicious site punya form tersembunyi:
         <form action="tokoonline.com/cart/checkout" method="POST">
         Form ini auto-submit, dan karena browser masih punya
         session tokoonline, request berhasil!

         SOLUSI:
         Setiap form harus punya token random yang hanya diketahui
         server. Token ini disimpan di meta tag agar bisa diakses
         JavaScript untuk AJAX request.

         JavaScript mengambil token:
         const token = document.querySelector('meta[name="csrf-token"]').content --}}

    <title>@yield('title', 'Toko Online') - {{ config('app.name') }}</title>
    {{-- ↑ PENJELASAN DIRECTIVE @yield():

         @yield('nama', 'default') adalah PLACEHOLDER
         Akan diisi oleh child template dengan @section()

         ALUR:
         1. Child: @section('title', 'Katalog Produk')
         2. Parent: @yield('title') diganti 'Katalog Produk'
         3. Hasil: "Katalog Produk - Toko Online"

         'Toko Online' adalah DEFAULT jika child tidak set title

         {{ config('app.name') }}
         Mengambil nilai APP_NAME dari file .env
         .env: APP_NAME="Toko Online"
         Hasil: "Toko Online" --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- ↑ DIRECTIVE @vite() - Menyisipkan CSS dan JS

         CARA KERJA VITE:

         DEVELOPMENT (npm run dev):
         Vite berjalan sebagai server di port 5173
         @vite() menghasilkan:
         <script type="module" src="http://localhost:5173/@vite/client">
         <script type="module" src="http://localhost:5173/resources/js/app.js">
         Hot Module Replacement (HMR) aktif - edit langsung terlihat

         PRODUCTION (npm run build):
         File dikompilasi ke public/build/
         @vite() membaca manifest.json untuk dapetin nama file dengan hash
         <link rel="stylesheet" href="/build/assets/app-Dk3J8sH2.css">
         <script type="module" src="/build/assets/app-L3hF9kD1.js">
         Hash di nama file untuk cache busting --}}

    @stack('styles')
    {{-- ↑ STACK adalah tempat kumpulan konten dari child

         BERBEDA DENGAN @yield:
         - @yield: 1 child hanya 1x isi, replace
         - @stack: banyak @push bisa ditumpuk

         CARA PAKAI DI CHILD:
         @push('styles')
             <link rel="stylesheet" href="/custom.css">
         @endpush

         @push('styles')
             <style>.product-card { border: 1px solid red }</style>
         @endpush

         HASIL: Kedua block ditampilkan --}}
</head>

<script>
  /**
   * Fungsi AJAX untuk Toggle Wishlist
   * Menggunakan Fetch API (Modern JS) daripada jQuery.
   */
  async function toggleWishlist(productId) {
    try {
      // 1. Ambil CSRF token dari meta tag HTML
      // Laravale mewajibkan token ini untuk setiap request POST demi keamanan.
      const token = document.querySelector('meta[name="csrf-token"]').content;

      // 2. Kirim Request ke Server
      const response = await fetch(`/wishlist/toggle/${productId}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token, // Tempel token di header
        },
      });

      // 3. Handle jika user belum login (Error 401 Unauthorized)
      if (response.status === 401) {
        window.location.href = "/login"; // Lempar ke halaman login
        return;
      }

      // 4. Baca respon JSON dari server
      const data = await response.json();

      if (data.status === "success") {
        // 5. Update UI tanpa reload halaman
        updateWishlistUI(productId, data.added); // Ganti warna ikon
        updateWishlistCounter(data.count); // Update angka di header
        showToast(data.message); // Tampilkan notifikasi
      }
    } catch (error) {
      console.error("Error:", error);
      showToast("Terjadi kesalahan sistem.", "error");
    }
  }

  function updateWishlistUI(productId, isAdded) {
    // Cari semua tombol wishlist untuk produk ini (bisa ada di card & detail page)
    const buttons = document.querySelectorAll(`.wishlist-btn-${productId}`);

    buttons.forEach((btn) => {
      const icon = btn.querySelector("i"); // Menggunakan tag <i> untuk Bootstrap Icons
      if (isAdded) {
        // Ubah jadi merah solid (Love penuh)
        icon.classList.remove("bi-heart", "text-secondary");
        icon.classList.add("bi-heart-fill", "text-danger");
      } else {
        // Ubah jadi abu-abu outline (Love kosong)
        icon.classList.remove("bi-heart-fill", "text-danger");
        icon.classList.add("bi-heart", "text-secondary");
      }
    });
  }

  function updateWishlistCounter(count) {
    const badge = document.getElementById("wishlist-count");
    if (badge) {
      badge.innerText = count;
      // Bootstrap badge display toggle logic
      badge.style.display = count > 0 ? "inline-block" : "none";
    }
  }
</script>
<body>
    @include('partials.navbar')
    {{-- ↑ DIRECTIVE @include() - Menyisipkan File Lain

         Sama seperti copy-paste isi file ke sini

         @include('partials.navbar') artinya:
         Sisipkan file: resources/views/partials/navbar.blade.php

         PATH MENGGUNAKAN DOT NOTATION:
         partials.navbar = partials/navbar.blade.php
         admin.products.form = admin/products/form.blade.php

         PASSING DATA KE INCLUDE:
         @include('partials.product-card', ['product' => $item])
         Variabel $product tersedia di dalam product-card.blade.php

         BEDANYA DENGAN @extends:
         - @extends: inheritance (parent-child relationship)
         - @include: composition (menyisipkan partial/fragment) --}}

    <div class="container mt-3">
        @include('partials.flash-messages')
    </div>

    <main class="min-vh-100">
    {{-- ↑ min-vh-100 = minimum 100% viewport height
           Agar footer tetap di bawah meski konten sedikit --}}

        @yield('content')
        {{-- ↑ CONTENT UTAMA dari child template

             Di child:
             @section('content')
                 <h1>Selamat Datang</h1>
                 <p>Ini konten halaman</p>
             @endsection

             @yield('content') akan diganti dengan semua konten
             di dalam @section('content') child --}}
    </main>

    @include('partials.footer')

    @stack('scripts')
    {{-- ↑ Tempat menumpuk JavaScript dari child

         Child bisa push script khusus untuk halaman itu:
         @push('scripts')
         <script>
             console.log('Ini hanya di halaman detail produk');
         </script>
         @endpush --}}
</body>
</html>