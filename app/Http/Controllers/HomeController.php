<?php
// ================================================================
// FILE: app/Http/Controllers/HomeController.php
// ================================================================
//
// TUJUAN:
// Controller ini menangani halaman BERANDA (Homepage) website.
// Halaman beranda adalah halaman pertama yang dilihat pengunjung
// saat membuka website. Harus menampilkan konten menarik yang
// mengundang visitor untuk explore lebih lanjut.
//
// KONTEN YANG DITAMPILKAN:
// 1. Hero section (banner utama) - biasanya berisi promo
// 2. Kategori populer - membantu navigasi cepat
// 3. Produk unggulan (featured) - produk pilihan toko
// 4. Produk terbaru - menunjukkan toko aktif update
//
// ================================================================

namespace App\Http\Controllers;
// ↑ NAMESPACE: Alamat/lokasi class ini dalam struktur folder
//   App = folder app/
//   Http = folder Http/
//   Controllers = folder Controllers/
//   Jadi file ini ada di: app/Http/Controllers/HomeController.php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
// ↑ USE STATEMENT: Mengimpor class yang akan digunakan
//   - Category: Model untuk tabel categories
//   - Product: Model untuk tabel products
//   - Request: Object yang berisi data HTTP request

class HomeController extends Controller
// ↑ CLASS DEFINITION:
//   - HomeController adalah nama class (harus sama dengan nama file)
//   - extends Controller: mewarisi fitur dari Controller dasar Laravel
//     (seperti middleware, response helpers, dll)
{
    /**
     * Menampilkan halaman beranda.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    // ↑ METHOD INDEX:
    //   - public: bisa diakses dari luar class (oleh Router)
    //   - function index(): nama method, akan dipanggil oleh route
    //   - Tidak ada parameter karena homepage tidak butuh input user
    {
        // ============================================================
        // STEP 1: AMBIL DATA KATEGORI UNTUK SECTION "KATEGORI POPULER"
        // ============================================================

        $categories = Category::query()
        // ↑ Category::query() memulai "Query Builder" baru
        //   Sama seperti: SELECT * FROM categories
        //   Kita pakai query() agar bisa chaining method

            ->active()
            // ↑ SCOPE METHOD dari Model Category
            //   Didefinisikan di Model: public function scopeActive($query)
            //   Menambahkan: WHERE is_active = true
            //   Jadi query jadi: SELECT * FROM categories WHERE is_active = 1

            ->withCount(['activeProducts' => function($q) {
            // ↑ withCount() menghitung jumlah relasi tanpa load semua data
            //   Hasil: menambah kolom virtual "active_products_count"
            //   Query: SELECT categories.*, (SELECT COUNT(*) FROM products...) as active_products_count
            //
            //   ['activeProducts' => function($q)] = custom count dengan kondisi
                $q->where('is_active', true)
                  ->where('stock', '>', 0);
                // ↑ Hanya hitung produk yang:
                //   - is_active = true (tidak diarsipkan)
                //   - stock > 0 (masih ada stok)
            }])

            ->having('active_products_count', '>', 0)
            // ↑ HAVING bukan WHERE karena active_products_count adalah kolom virtual
            //   (hasil dari aggregate function)
            //   Filter: hanya kategori yang punya minimal 1 produk aktif
            //   Kategori kosong tidak ditampilkan

            ->orderBy('name')
            // ↑ Urutkan berdasarkan nama alfabet (A-Z)
            //   ORDER BY name ASC

            ->take(6)
            // ↑ Batasi hanya 6 kategori pertama
            //   LIMIT 6
            //   Kenapa 6? Karena di grid 6 kolom pas untuk responsive

            ->get();
            // ↑ EKSEKUSI QUERY dan ambil hasilnya
            //   Return: Collection berisi object Category
            //
            //   QUERY SQL LENGKAP:
            //   SELECT categories.*,
            //          (SELECT COUNT(*) FROM products
            //           WHERE products.category_id = categories.id
            //           AND is_active = 1 AND stock > 0) as active_products_count
            //   FROM categories
            //   WHERE is_active = 1
            //   HAVING active_products_count > 0
            //   ORDER BY name ASC
            //   LIMIT 6

        // ============================================================
        // STEP 2: AMBIL PRODUK UNGGULAN (FEATURED PRODUCTS)
        // ============================================================

        $featuredProducts = Product::query()
        // ↑ Mulai query baru untuk tabel products

            ->with(['category', 'primaryImage'])
            // ↑ EAGER LOADING - SANGAT PENTING UNTUK PERFORMA!
            //
            //   MASALAH N+1 QUERY:
            //   Tanpa with(), jika kita punya 8 produk dan loop:
            //   @foreach($products as $p) {{ $p->category->name }} @endforeach
            //
            //   Akan terjadi:
            //   1 query ambil products
            //   + 8 query ambil category (1 per produk)
            //   + 8 query ambil image (1 per produk)
            //   = 17 query total!
            //
            //   DENGAN with():
            //   1 query: SELECT * FROM products WHERE ...
            //   1 query: SELECT * FROM categories WHERE id IN (1,2,3...)
            //   1 query: SELECT * FROM product_images WHERE product_id IN (1,2...) AND is_primary = 1
            //   = 3 query saja! Jauh lebih cepat!

            ->active()
            // ↑ Scope: WHERE is_active = true

            ->inStock()
            // ↑ Scope: WHERE stock > 0
            //   Produk yang stoknya habis tidak ditampilkan

            ->featured()
            // ↑ Scope: WHERE is_featured = true
            //   Produk yang di-flag featured oleh admin

            ->latest()
            // ↑ ORDER BY created_at DESC
            //   Tampilkan yang terbaru duluan

            ->take(8)
            // ↑ LIMIT 8 produk
            //   8 = 2 baris x 4 kolom di desktop

            ->get();
            // ↑ Eksekusi dan ambil hasil

        // ============================================================
        // STEP 3: AMBIL PRODUK TERBARU (LATEST PRODUCTS)
        // ============================================================

        $latestProducts = Product::query()
            ->with(['category', 'primaryImage'])
            ->active()
            ->inStock()
            // Tidak pakai ->featured() karena kita mau semua produk,
            // bukan hanya yang featured
            ->latest()
            // ↑ Urutkan dari yang paling baru
            ->take(8)
            ->get();

        // ============================================================
        // STEP 4: KIRIM DATA KE VIEW (BLADE)
        // ============================================================

        return view('home', compact(
            'categories',
            'featuredProducts',
            'latestProducts'
        ));
        // ↑ PENJELASAN:
        //
        //   view('home', [...]) artinya:
        //   - Cari file: resources/views/home.blade.php
        //   - Kirim data ke file tersebut
        //
        //   compact('categories', 'featuredProducts', 'latestProducts')
        //   adalah shortcut untuk:
        //   [
        //       'categories' => $categories,
        //       'featuredProducts' => $featuredProducts,
        //       'latestProducts' => $latestProducts,
        //   ]
        //
        //   Di dalam view, sekarang kita bisa akses:
        //   $categories, $featuredProducts, $latestProducts
    }
}