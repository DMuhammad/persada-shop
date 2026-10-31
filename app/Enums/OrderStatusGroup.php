<?php

namespace App\Enums;

/**
 * Buckets the storefront filters the order history by, each covering the
 * statuses a customer reads as a single stage.
 */
enum OrderStatusGroup: int
{
    case UNPAID = 0;
    case PROCESSING = 1;
    case SHIPPED = 2;
    case COMPLETED = 3;

    /**
     * Returns the label shown on the order history tabs.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Belum Dibayar',
            self::PROCESSING => 'Diproses',
            self::SHIPPED => 'Dikirim',
            self::COMPLETED => 'Selesai',
        };
    }

    /**
     * Returns the order statuses this bucket covers.
     *
     * @return array<int, OrderStatus>
     */
    public function statuses(): array
    {
        return match ($this) {
            self::UNPAID => [OrderStatus::PENDING_PAYMENT],
            self::PROCESSING => [OrderStatus::PAID, OrderStatus::PROCESSING],
            self::SHIPPED => [OrderStatus::SHIPPED],
            self::COMPLETED => [OrderStatus::DELIVERED, OrderStatus::COMPLETED],
        };
    }
}
