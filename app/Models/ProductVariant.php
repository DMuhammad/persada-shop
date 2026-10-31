<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'moq',
        'weight_in_grams',
        'length_in_cm',
        'width_in_cm',
        'height_in_cm'
    ];

    /**
     * Stock lives in its own table, so the balance is always eager loaded and
     * exposed as variant attributes for readers and serialized responses.
     */
    protected $with = ['stockRecord'];

    protected $appends = ['stock', 'reserved_stock', 'available_stock'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Stock\Stock::class);
    }

    /**
     * Returns the physical quantity held for this variant.
     */
    public function getStockAttribute(): int
    {
        return $this->stockRecord?->quantity ?? 0;
    }

    /**
     * Returns the quantity held by orders that are waiting for payment.
     */
    public function getReservedStockAttribute(): int
    {
        return $this->stockRecord?->reserved_quantity ?? 0;
    }

    /**
     * Returns the quantity a customer may still order.
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->stock - $this->reserved_stock;
    }
}
