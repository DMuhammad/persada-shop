<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Stock\StockService;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Creates a variant with a starting balance through the stock service.
 */
function variantWithStock(int $quantity = 0): ProductVariant
{
    $category = Category::create(['name' => 'Kategori Uji']);

    $product = Product::create([
        'name' => 'Produk Uji',
        'slug' => 'produk-uji',
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    $variant = $product->variants()->create([
        'name' => 'Varian Uji',
        'price' => 10000,
        'moq' => 1,
    ]);

    app(StockService::class)->initialize($variant, $quantity);

    return $variant->refresh();
}

/**
 * Creates an unpaid order holding the given quantity of a variant, the way
 * checkout leaves it behind.
 */
function unpaidOrderFor(ProductVariant $variant, int $quantity): Order
{
    $order = Order::create([
        'order_number' => 'ORD-STATUS-' . Str::upper(Str::random(6)),
        'user_id' => User::factory()->create()->id,
        'shipping_address' => [
            'receiver_name' => 'Penerima Uji',
            'phone' => '08123456789',
            'street_address' => 'Jalan Uji 1',
            'area_name' => 'Bandung',
        ],
        'subtotal' => 10000,
        'shipping_cost' => 0,
        'grand_total' => 10000,
        'status' => OrderStatus::PENDING_PAYMENT,
    ]);

    $order->items()->create([
        'product_variant_id' => $variant->id,
        'product_name' => 'Produk Uji',
        'variant_name' => 'Varian Uji',
        'price_at_purchase' => 10000,
        'quantity' => $quantity,
        'weight_in_grams' => 100,
    ]);

    app(StockService::class)->reserve($variant, $quantity);

    return $order;
}
