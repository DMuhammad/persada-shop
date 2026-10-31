<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\Stock\StockService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Single entry point for moving an order to another status, so the stock and
 * the notifications a move carries cannot be forgotten by a caller.
 */
class OrderStatusService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Moves an order to another status and applies what the move means for its
     * stock. Returns false when the order already sits on that status, which is
     * what a repeated payment webhook looks like.
     */
    public function change(Order $order, OrderStatus $status): bool
    {
        return DB::transaction(function () use ($order, $status) {
            $previous = $this->lockStatus($order);

            if ($previous === $status) {
                return false;
            }

            $order->status = $status;

            if ($status === OrderStatus::PAID) {
                $order->paid_at = now();
            }

            $order->save();

            $this->moveStock($order, $previous, $status);
            $this->notifyAdmins($order, $previous, $status);

            return true;
        });
    }

    /**
     * Reads the stored status with the order row locked, so two webhooks for the
     * same order cannot both act on it.
     */
    private function lockStatus(Order $order): OrderStatus
    {
        return Order::whereKey($order->getKey())->lockForUpdate()->value('status');
    }

    /**
     * Applies a status move to the warehouse: a paid order turns its hold into
     * a sale, while an undone one frees the hold or takes the goods back.
     */
    private function moveStock(Order $order, OrderStatus $previous, OrderStatus $status): void
    {
        foreach ($order->items as $item) {
            $variant = $item->productVariant;

            if (! $variant) {
                continue;
            }

            match (true) {
                $status === OrderStatus::PAID && $previous->awaitsPayment() => $this->stock->sell($variant, $item->quantity, $order),
                $status->isUndone() && $previous->awaitsPayment() => $this->stock->release($variant, $item->quantity),
                $status->isUndone() && $previous->isSettled() => $this->stock->restock(
                    $variant,
                    $item->quantity,
                    $order,
                    "Pengembalian stok pesanan {$order->order_number}.",
                ),
                default => null,
            };
        }
    }

    /**
     * Tells the admins about a move they have to act on. A mail or database
     * failure here never rolls the status change back.
     */
    private function notifyAdmins(Order $order, OrderStatus $previous, OrderStatus $status): void
    {
        $notification = match (true) {
            $status === OrderStatus::PAID && $previous->awaitsPayment() => new PaymentConfirmedNotification($order),
            $status === OrderStatus::CANCELLED && $previous->awaitsPayment() => new OrderCancelledNotification($order),
            default => null,
        };

        if (! $notification) {
            return;
        }

        try {
            $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, $notification);
            }
        } catch (Exception $e) {
            Log::error('Gagal mengirim notifikasi perubahan status pesanan: ' . $e->getMessage());
        }
    }
}
