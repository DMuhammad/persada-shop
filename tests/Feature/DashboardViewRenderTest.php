<?php

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Stock\StockMovementRepository;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

it('renders the stock and product dashboard pages', function () {
    $this->actingAs(User::factory()->create());

    // Rendering a view directly skips the middleware that shares the error bag.
    view()->share('errors', new ViewErrorBag);

    $product = Product::create([
        'name' => 'Produk Uji',
        'slug' => 'produk-uji',
        'category_id' => Category::create(['name' => 'Kategori Uji'])->id,
        'status' => 'active',
    ]);

    $variant = $product->variants()->create(['name' => 'Varian Uji', 'price' => 10000, 'moq' => 1]);
    app(StockService::class)->initialize($variant, 5);

    $movements = app(StockMovementRepository::class);
    $products = Product::with('variants')->get(['id', 'name']);

    $stockPage = view('pages.dashboard.stock.index', [
        'movements' => $movements->paginateFiltered([]),
        'filters' => [],
        'products' => $products,
        'types' => StockMovementType::cases(),
        'manualTypes' => StockMovementType::manualCases(),
    ])->render();

    $detailPage = view('pages.dashboard.product.detail', [
        'product' => $product->load(['variants', 'images', 'category']),
        'movements' => $movements->latestForProduct($product->id),
        'manualTypes' => StockMovementType::manualCases(),
    ])->render();

    $indexPage = view('pages.dashboard.product.index', [
        'products' => Product::with(['category', 'variants', 'images'])->paginate(10),
        'categories' => Category::all(),
    ])->render();

    expect($stockPage)->toContain('Catat Mutasi Stok', 'Saldo Sebelum', 'Varian Uji (stok 5)')
        ->and($detailPage)->toContain('open-stock-movement', 'Tambah Varian', 'Tambah Gambar')
        ->and($indexPage)->toContain('Tambah Produk');
});
