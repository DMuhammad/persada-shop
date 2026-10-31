<?php

namespace App\Repositories\Stock;

use App\Models\Stock\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockMovementRepository
{
    /**
     * Stores a movement record.
     */
    public function create(array $attributes): StockMovement
    {
        return StockMovement::create($attributes);
    }

    /**
     * Returns movements newest first, filtered by product, type and date range.
     *
     * @param  array<string, string|null>  $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['stock.productVariant.product', 'order', 'creator'])
            ->when(
                $filters['product_id'] ?? null,
                fn($query, $productId) => $query->whereHas(
                    'stock.productVariant',
                    fn($variant) => $variant->where('product_id', $productId)
                )
            )
            ->when($filters['type'] ?? null, fn($query, $type) => $query->where('type', $type))
            ->when($filters['start_date'] ?? null, fn($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Returns the latest movements of every variant of a product.
     */
    public function latestForProduct(string $productId, int $limit = 10): Collection
    {
        return StockMovement::query()
            ->with(['stock.productVariant', 'order', 'creator'])
            ->whereHas('stock.productVariant', fn($variant) => $variant->where('product_id', $productId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
