<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk dengan pagination & filtering.
     */
    public function index(Request $request): View
    {
        $products = Product::query()
            ->select('id', 'name', 'price', 'slug', 'category_id', 'created_at') // Select kolom penting saja
            ->with(['category:id,name', 'primaryImage:id,product_id,image_path,is_primary']) // Eager Loading
            ->when($request->search, fn($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->category, fn($query, $categoryId) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Ambil kategori dari cache
        $categories = Cache::remember('global_categories', 3600, function () {
            return Category::select('id', 'name')
                ->withCount('products')
                ->orderBy('name')
                ->get();
        });

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Form tambah produk.
     */
    public function create(): View
    {
        $categories = Cache::remember('global_categories', 3600, function () {
            return Category::select('id', 'name')
                ->withCount('products')
                ->orderBy('name')
                ->get();
        });

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Simpan produk baru (transaction & upload gambar).
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::create($request->validated());

            if ($request->hasFile('images')) {
                $this->uploadImages($request->file('images'), $product);
            }

            DB::commit();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan produk: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail produk.
     */
    public function show(Product $product): View
    {
        $product->load(['category:id,name', 'images:id,product_id,image_path,is_primary', 'orderItems']);

        return view('admin.products.show', compact('product'));
    }

    /**
     * Form edit produk.
     */
    public function edit(Product $product): View
    {
        $categories = Cache::remember('global_categories', 3600, function () {
            return Category::select('id', 'name')
                ->withCount('products')
                ->orderBy('name')
                ->get();
        });

        $product->load('images');

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update produk (transaction + gambar).
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $product->update($request->validated());

            if ($request->hasFile('images')) {
                $this->uploadImages($request->file('images'), $product);
            }

            if ($request->has('delete_images')) {
                $this->deleteImages($request->delete_images);
            }

            if ($request->has('primary_image')) {
                $this->setPrimaryImage($product, $request->primary_image);
            }

            DB::commit();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Hapus produk + gambar.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            $product->delete();

            return redirect()->route('admin.products.index')->with('success', 'Produk dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    // ---------------- Helper Methods ----------------
    protected function uploadImages(array $files, Product $product): void
    {
        $isFirst = $product->images()->count() === 0;

        foreach ($files as $index => $file) {
            $filename = 'product-' . $product->id . '-' . time() . '-' . $index . '.' . $file->extension();
            $path = $file->storeAs('products', $filename, 'public');

            $product->images()->create([
                'image_path' => $path,
                'is_primary' => $isFirst && $index === 0,
                'sort_order' => $product->images()->count() + $index,
            ]);
        }
    }

    protected function deleteImages(array $imageIds): void
    {
        $images = ProductImage::whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }
    }

    protected function setPrimaryImage(Product $product, int $imageId): void
    {
        $product->images()->update(['is_primary' => false]);
        $product->images()->where('id', $imageId)->update(['is_primary' => true]);
    }
}