<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock\StockMovement;
use App\Models\User;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Sums every movement of a variant, which must match the stored balance.
 */
function ledgerBalance(ProductVariant $variant): int
{
    return (int) StockMovement::where('stock_id', $variant->stockRecord->id)->sum('quantity');
}

it('logs the opening balance of a new variant', function () {
    $variant = variantWithStock(25);

    expect($variant->stock)->toBe(25)
        ->and(ledgerBalance($variant))->toBe(25);

    $movement = StockMovement::first();

    expect($movement->type)->toBe(StockMovementType::OPENING_BALANCE)
        ->and($movement->quantity)->toBe(25)
        ->and($movement->balance_before)->toBe(0)
        ->and($movement->balance_after)->toBe(25);
});

it('records incoming stock and keeps the balance traceable', function () {
    $variant = variantWithStock(10);

    app(StockService::class)->record($variant, StockMovementType::PURCHASE, 15, 'Nota 001');

    expect($variant->refresh()->stock)->toBe(25)
        ->and(ledgerBalance($variant))->toBe(25)
        ->and(StockMovement::latest('id')->first()->note)->toBe('Nota 001');
});

it('turns a reservation into a sale movement when the order is paid', function () {
    $variant = variantWithStock(10);
    $service = app(StockService::class);

    $service->reserve($variant, 4);
    $variant->refresh();

    expect($variant->stock)->toBe(10)
        ->and($variant->reserved_stock)->toBe(4)
        ->and($variant->available_stock)->toBe(6)
        ->and(StockMovement::count())->toBe(1);

    $service->sell($variant, 4, orderFor($variant));
    $variant->refresh();

    expect($variant->stock)->toBe(6)
        ->and($variant->reserved_stock)->toBe(0)
        ->and(ledgerBalance($variant))->toBe(6)
        ->and(StockMovement::latest('id')->first()->quantity)->toBe(-4);
});

it('refuses a reservation larger than the available stock', function () {
    $variant = variantWithStock(5);
    app(StockService::class)->reserve($variant, 5);

    app(StockService::class)->reserve($variant->refresh(), 1);
})->throws(InsufficientStockException::class);

it('refuses an outgoing movement that would make the balance negative', function () {
    $variant = variantWithStock(3);

    app(StockService::class)->record($variant, StockMovementType::SALE, 4);
})->throws(InsufficientStockException::class);

it('returns stock to the warehouse on a refund', function () {
    $variant = variantWithStock(10);
    $service = app(StockService::class);
    $order = orderFor($variant);

    $service->reserve($variant, 2);
    $service->sell($variant->refresh(), 2, $order);
    $service->restock($variant->refresh(), 2, $order, 'Dikembalikan pembeli');

    expect($variant->refresh()->stock)->toBe(10)
        ->and(ledgerBalance($variant))->toBe(10)
        ->and(StockMovement::latest('id')->first()->type)->toBe(StockMovementType::SALES_RETURN);
});

it('derives the direction of a correction from the counted balance', function () {
    $variant = variantWithStock(10);
    $service = app(StockService::class);

    $movement = $service->adjust($variant, 7, 'Hasil stock opname');

    expect($movement->type)->toBe(StockMovementType::ADJUSTMENT)
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->balance_before)->toBe(10)
        ->and($movement->balance_after)->toBe(7)
        ->and($variant->refresh()->stock)->toBe(7)
        ->and(ledgerBalance($variant))->toBe(7);

    expect($service->adjust($variant->refresh(), 7))->toBeNull()
        ->and(StockMovement::count())->toBe(2);
});

/**
 * Creates a minimal paid order used as the movement reference.
 */
function orderFor(ProductVariant $variant): Order
{
    $user = User::factory()->create();

    return Order::create([
        'order_number' => 'ORD-TEST-' . $variant->id,
        'user_id' => $user->id,
        'shipping_address' => ['city' => 'Bandung'],
        'subtotal' => 10000,
        'shipping_cost' => 0,
        'grand_total' => 10000,
        'status' => OrderStatus::PAID,
    ]);
}
