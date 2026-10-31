<?php

namespace App\Services\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Stock\Stock;
use App\Models\Stock\StockMovement;
use App\Repositories\Stock\StockMovementRepository;
use App\Repositories\Stock\StockRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Single entry point for every stock change. Each physical change is written as
 * a movement, so the balance in the stocks table can always be traced back to
 * its history.
 */
class StockService
{
    public function __construct(
        private readonly StockRepository $stocks,
        private readonly StockMovementRepository $movements,
    ) {}

    /**
     * Records a physical stock change and applies it to the balance. The sign
     * comes from the movement type, so the quantity is always given positive.
     *
     * @throws InsufficientStockException when the change would make the balance negative
     */
    public function record(
        ProductVariant $variant,
        StockMovementType $type,
        int $quantity,
        ?string $note = null,
        ?Order $order = null,
    ): StockMovement {
        if ($type->isAdjustment()) {
            throw new InvalidArgumentException('A correction is recorded with adjust(), which takes the counted balance.');
        }

        $quantity = $this->requirePositive($quantity);

        return DB::transaction(function () use ($variant, $type, $quantity, $note, $order) {
            $stock = $this->stocks->lockForVariant($variant->id);

            return $this->apply($variant, $stock, $type, $type->direction() * $quantity, $note, $order);
        });
    }

    /**
     * Records a counted balance as a correction. The direction follows the gap
     * between the stored balance and the counted one, and nothing is written
     * when the two already match.
     */
    public function adjust(ProductVariant $variant, int $countedQuantity, ?string $note = null): ?StockMovement
    {
        if ($countedQuantity < 0) {
            throw new InvalidArgumentException('A counted stock balance cannot be negative.');
        }

        return DB::transaction(function () use ($variant, $countedQuantity, $note) {
            $stock = $this->stocks->lockForVariant($variant->id);
            $change = $countedQuantity - $stock->quantity;

            if ($change === 0) {
                return null;
            }

            return $this->apply($variant, $stock, StockMovementType::ADJUSTMENT, $change, $note);
        });
    }

    /**
     * Creates the stock row of a new variant and logs its opening balance.
     */
    public function initialize(ProductVariant $variant, int $quantity): void
    {
        DB::transaction(function () use ($variant, $quantity) {
            $this->stocks->lockForVariant($variant->id);

            if ($quantity > 0) {
                $this->record($variant, StockMovementType::OPENING_BALANCE, $quantity);
            }
        });
    }

    /**
     * Holds stock for an order that is waiting for payment. Nothing leaves the
     * warehouse yet, so no movement is recorded.
     *
     * @throws InsufficientStockException when the available quantity is too low
     */
    public function reserve(ProductVariant $variant, int $quantity): Stock
    {
        $quantity = $this->requirePositive($quantity);

        return DB::transaction(function () use ($variant, $quantity) {
            $stock = $this->stocks->lockForVariant($variant->id);

            if ($stock->available_quantity < $quantity) {
                throw new InsufficientStockException($this->shortageMessage($variant, $stock->available_quantity));
            }

            return $this->stocks->updateBalance($stock, $stock->quantity, $stock->reserved_quantity + $quantity);
        });
    }

    /**
     * Frees a hold that is no longer needed, for a cancelled or expired order.
     * Clamped at zero so a repeated webhook cannot drive it below zero.
     */
    public function release(ProductVariant $variant, int $quantity): Stock
    {
        $quantity = $this->requirePositive($quantity);

        return DB::transaction(function () use ($variant, $quantity) {
            $stock = $this->stocks->lockForVariant($variant->id);
            $reserved = max(0, $stock->reserved_quantity - $quantity);

            return $this->stocks->updateBalance($stock, $stock->quantity, $reserved);
        });
    }

    /**
     * Turns the hold of a paid order into an outgoing sale movement.
     */
    public function sell(ProductVariant $variant, int $quantity, Order $order): StockMovement
    {
        return DB::transaction(function () use ($variant, $quantity, $order) {
            $this->release($variant, $quantity);

            return $this->record($variant, StockMovementType::SALE, $quantity, null, $order);
        });
    }

    /**
     * Returns the goods of a cancelled or refunded order to the warehouse.
     */
    public function restock(ProductVariant $variant, int $quantity, Order $order, ?string $note = null): StockMovement
    {
        return $this->record($variant, StockMovementType::SALES_RETURN, $quantity, $note, $order);
    }

    /**
     * Writes a movement and moves the balance along with it.
     *
     * @throws InsufficientStockException when the change would make the balance negative
     */
    private function apply(
        ProductVariant $variant,
        Stock $stock,
        StockMovementType $type,
        int $change,
        ?string $note,
        ?Order $order = null,
    ): StockMovement {
        $balanceBefore = $stock->quantity;
        $balanceAfter = $balanceBefore + $change;

        if ($balanceAfter < 0) {
            throw new InsufficientStockException($this->shortageMessage($variant, $balanceBefore));
        }

        $this->stocks->updateBalance($stock, $balanceAfter, $stock->reserved_quantity);

        return $this->movements->create([
            'stock_id' => $stock->id,
            'type' => $type,
            'quantity' => $change,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'order_id' => $order?->id,
            'created_by' => auth()->id(),
            'note' => $note,
        ]);
    }

    /**
     * Guards that a mutation carries a usable quantity.
     */
    private function requirePositive(int $quantity): int
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Stock movement quantity must be greater than zero.');
        }

        return $quantity;
    }

    /**
     * Builds the message shown when a variant cannot cover a mutation.
     */
    private function shortageMessage(ProductVariant $variant, int $remaining): string
    {
        $product = $variant->product?->name ?? 'produk';

        return "Stok {$product} - {$variant->name} tidak mencukupi (tersisa {$remaining}).";
    }
}
