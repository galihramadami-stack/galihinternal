<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Menampilkan halaman catalog publik dengan fitur filter lengkap.
     * Logika filtering dibangun secara dinamis menggunakan chain method.
     */
    public function index(Request $request)
    {
        // 1. BASE QUERY
        // Mulai dengan query dasar: ambil produk
        // ->with(): Eager Load relasi category & primaryImage untuk menghindari query berulang (N+1).
        // ->available(): Query Scope (lokal di model Product) yang memfilter produk aktif & stok > 0.
        $query = Product::query()
            ->with(['category', 'primaryImage'])
            ->available(); // Scope 'available'

        // 2. FILTERING PIPELINE
        // Menerapkan filter hanya jikda user mengirimkan parameter tertentu.

        // Filter: Search keyword (?q=iphone)
        if ($request->filled('q')) {
            $query->search($request->q); // Scope 'search'
        }

        // Filter: Category by Slug (?category=elektronik)
        if ($request->filled('category')) {
            $query->byCategory($request->category); // Scope 'byCategory'
        }

        // Filter: Price Range (?min_price=1000&max_price=50000)
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // 3. SORTING LOGIC (?sort=price_asc)
        // Default sorting adalah 'newest' (terbaru).
        $sort = $request->get('sort', 'newest');

        // Menggunakan method when() untuk penulisan if-else yang lebih bersih (fungsional).
        $query->when($sort === 'price_asc', fn($q) => $q->orderBy('price', 'asc'))
              ->when($sort === 'price_desc', fn($q) => $q->orderBy('price', 'desc'))
              ->when($sort === 'name_asc', fn($q) => $q->orderBy('name', 'asc'))
              ->when($sort === 'name_desc', fn($q) => $q->orderBy('name', 'desc'))
              ->when($sort === 'newest', fn($q) => $q->latest());

        // 4. EXECUTE & PAGINATE
        // Jalankan query dan ambil 12 produk per halaman.
        // withQueryString(): Menempelkan parameter filter saat ini ke link pagination (Next/Prev).
        // Tanpa ini, jika user klik halaman 2, filter pencariannya akan hilang.
        $products = $query->paginate(12)->withQueryString();

        // 5. DATA PENDUKUNG VIEW (SIDEBAR)

        // Ambil daftar kategori, TAPI:
        // ->withCount(): Hitung jumlah produk available di dalamnya.
        // ->having(): Hanya ambil kategori yang PUNYA produk (minimal 1).
        // Ini mencegah user memilih kategori kosong.
        $categories = Category::active()
            ->withCount(['products' => fn($q) => $q->available()])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get();

        // Hitung Range harga global untuk keperluan UI (misal slider harga minimum-maksimum).
        // selectRaw lebih efisien daripada tarik semua data lalu di loop php.
        $priceRange = Product::available()
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        return view('catalog.index', compact('products', 'categories', 'priceRange'));
    }

    /**
     * Menampilkan detail produk (Single Product Page).
     */
    public function show($slug)
    {
        // Cari produk berdasarkan SLUG, bukan ID (SEO Friendly).
        // PENTING: Gunakan scope available() agar user tidak bisa akses produk yang non-aktif via URL langsung.
        $product = Product::available()
            ->with(['category', 'images']) // Load semua gambar galeri
            ->where('slug', $slug)
            ->firstOrFail(); // 404 jika tidak ketemu

        return view('catalog.show', compact('product'));
    }
}