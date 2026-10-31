<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Stock\StockMovementRepository;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductRequest;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category', 'variants', 'images'])
            ->latest()
            ->paginate(10);
        $categories = Category::all();
        return view('pages.dashboard.product.index', compact('products', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        DB::transaction(function () use ($request) {
            $validated = $request->validated();
            Product::create($validated);
        });

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $validated = $request->validated();
            $product->update($validated);
        });


        return redirect()->route('master.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();
        return redirect()->route('master.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function detail(Product $product, StockMovementRepository $movements)
    {
        $product->load(['variants', 'images', 'category']);

        return view('pages.dashboard.product.detail', [
            'product' => $product,
            'movements' => $movements->latestForProduct($product->id),
            'manualTypes' => StockMovementType::manualCases(),
        ]);
    }
}
