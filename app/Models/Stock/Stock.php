<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Current stock balance of a product variant. The balance is the sum of its
 * movements, which stay as the audit trail behind every change.
 */
class Stock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_variant_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $appends = ['available_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }

    public function productVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductVariant::class);
    }

    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Returns the quantity that is not held by unpaid orders.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
