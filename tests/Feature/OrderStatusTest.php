<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Stock\StockMovement;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('turns the hold of an unpaid order into a sale when it is paid', function () {
    $variant = variantWithStock(10);
    $order = unpaidOrderFor($variant, 3);

    expect(app(OrderStatusService::class)->change($order, OrderStatus::PAID))->toBeTrue();

    expect($variant->refresh()->stock)->toBe(7)
        ->and($variant->reserved_stock)->toBe(0)
        ->and($order->refresh()->status)->toBe(OrderStatus::PAID)
        ->and($order->paid_at)->not->toBeNull()
        ->and(StockMovement::latest('id')->first()->type)->toBe(StockMovementType::SALE);
});

it('only frees the hold when an unpaid order is cancelled', function () {
    $variant = variantWithStock(10);
    $order = unpaidOrderFor($variant, 3);

    app(OrderStatusService::class)->change($order, OrderStatus::CANCELLED);

    expect($variant->refresh()->stock)->toBe(10)
        ->and($variant->reserved_stock)->toBe(0)
        ->and(StockMovement::count())->toBe(1);
});

it('takes the goods back when a settled order is refunded', function () {
    $variant = variantWithStock(10);
    $order = unpaidOrderFor($variant, 3);
    $service = app(OrderStatusService::class);

    $service->change($order, OrderStatus::PAID);
    $service->change($order, OrderStatus::REFUNDED);

    expect($variant->refresh()->stock)->toBe(10)
        ->and($variant->reserved_stock)->toBe(0)
        ->and(StockMovement::latest('id')->first()->type)->toBe(StockMovementType::SALES_RETURN);
});

it('leaves the stock alone when the order already has the status', function () {
    $variant = variantWithStock(10);
    $order = unpaidOrderFor($variant, 3);
    $service = app(OrderStatusService::class);

    $service->change($order, OrderStatus::PAID);

    expect($service->change($order, OrderStatus::PAID))->toBeFalse()
        ->and($variant->refresh()->stock)->toBe(7)
        ->and(StockMovement::count())->toBe(2);
});
