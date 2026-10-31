<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Stock\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product, StockService $stockService)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'moq' => 'required|integer|min:1',
            'weight_in_grams' => 'nullable|integer|min:0',
            'length_in_cm' => 'nullable|integer|min:0',
            'width_in_cm' => 'nullable|integer|min:0',
            'height_in_cm' => 'nullable|integer|min:0',
        ]);

        $variant = $product->variants()->create(Arr::except($validated, 'stock'));

        // The starting stock is the only balance an admin may type in; every
        // later change has to go through a stock movement.
        $stockService->initialize($variant, (int) $validated['stock']);

        return redirect()->route('master.products.detail', $product->id)
            ->with('success', 'Varian produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku,' . $variant->id,
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'moq' => 'required|integer|min:1',
            'weight_in_grams' => 'required|integer|min:0',
            'length_in_cm' => 'nullable|integer|min:0',
            'width_in_cm' => 'nullable|integer|min:0',
            'height_in_cm' => 'nullable|integer|min:0',
        ]);

        $variant->update($validated);

        return redirect()->route('master.products.detail', $product->id)
            ->with('success', 'Varian produk berhasil diperbarui.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $variant->delete();

        return redirect()->route('master.products.detail', $product->id)
            ->with('success', 'Varian produk berhasil dihapus.');
    }
}
