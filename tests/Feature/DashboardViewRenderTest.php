<?php

use App\Enums\OrderStatus;
use App\Enums\OrderStatusGroup;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Order;
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

it('renders every order page with the status labels of the enum', function () {
    $this->actingAs(User::factory()->create());
    view()->share('errors', new ViewErrorBag);

    $order = unpaidOrderFor(variantWithStock(10), 2);
    $orders = Order::with('items.productVariant.product.primaryImage')->paginate(10);

    $pages = [
        view('pages.dashboard.order', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'currentStatus' => null,
        ])->render(),
        view('pages.dashboard.order-detail', [
            'order' => $order->load(['user', 'items']),
            'statuses' => OrderStatus::cases(),
        ])->render(),
        view('pages.home.order', [
            'orders' => $orders,
            'groups' => OrderStatusGroup::cases(),
            'group' => null,
        ])->render(),
        view('pages.home.order-detail', ['order' => $order])->render(),
    ];

    foreach ($pages as $page) {
        expect($page)->toContain(OrderStatus::PENDING_PAYMENT->label())
            ->and($page)->not->toContain('pending_payment');
    }

    // An unpaid order never carries the paid stamp on its invoice.
    expect(view('pages.invoice', ['order' => $order])->render())->not->toContain('Lunas');
});
