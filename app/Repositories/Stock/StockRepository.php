<?php

namespace App\Repositories\Stock;

use App\Models\Stock\Stock;

class StockRepository
{
    /**
     * Returns the stock row of a variant locked for update, creating it when
     * the variant has no balance yet.
     */
    public function lockForVariant(string $productVariantId): Stock
    {
        $stock = Stock::where('product_variant_id', $productVariantId)->lockForUpdate()->first();

        return $stock ?? Stock::create(['product_variant_id' => $productVariantId]);
    }

    /**
     * Persists new balance values on a stock row.
     */
    public function updateBalance(Stock $stock, int $quantity, int $reservedQuantity): Stock
    {
        $stock->update([
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
        ]);

        return $stock;
    }
}
