<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
{
    $cart = $this->cartService->getCart();
    $cart->load(['items.product.primaryImage']);

    $cartItems = $cart->items->map(function ($item) {
        return [
            'product' => $item->product,
            'quantity' => $item->quantity,
            'subtotal' => $item->quantity * $item->product->display_price,
        ];
    });

    $totalQuantity = $cartItems->sum('quantity');
    $total = $cartItems->sum('subtotal');

    return view('cart.index', compact('cartItems', 'totalQuantity', 'total'));
}


    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $this->cartService->addProduct($product, $request->quantity);

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang!');
    }

    public function update(Request $request, $itemId)
    {
        $request->validate(['quantity' => 'required|integer|min:0']);
        $this->cartService->updateQuantity($itemId, $request->quantity);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function remove($itemId)
    {
        $this->cartService->removeItem($itemId);
        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}