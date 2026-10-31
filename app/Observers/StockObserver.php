<?php

namespace App\Observers;

use App\Models\Stock\Stock;
use App\Models\User;
use App\Notifications\ProductLowStockNotification;
use Illuminate\Support\Facades\Notification;

class StockObserver
{
    /**
     * Notifies administrators the first time a balance drops to the low stock
     * threshold, so a single order cannot spam the same warning.
     */
    public function updated(Stock $stock): void
    {
        $lowStockThreshold = 10;

        if (! $stock->isDirty('quantity')) {
            return;
        }

        $originalQuantity = $stock->getOriginal('quantity');

        if ($originalQuantity > $lowStockThreshold && $stock->quantity <= $lowStockThreshold) {
            $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ProductLowStockNotification($stock->productVariant));
            }
        }
    }
}
