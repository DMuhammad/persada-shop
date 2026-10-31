<?php

namespace App\Enums;

/**
 * Stages an order passes through. A settled order has had its goods taken out
 * of the warehouse and counts as revenue, while an undone one gives them back.
 */
enum OrderStatus: int
{
    case PENDING_PAYMENT = 0;
    case PAID = 1;
    case PROCESSING = 2;
    case SHIPPED = 3;
    case DELIVERED = 4;
    case COMPLETED = 5;
    case CANCELLED = 6;
    case REFUNDED = 7;

    /**
     * Returns the label shown in the dashboard and the storefront.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::PROCESSING => 'Diproses',
            self::SHIPPED => 'Dikirim',
            self::DELIVERED => 'Diterima',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            self::REFUNDED => 'Dana Dikembalikan',
        };
    }

    /**
     * Returns the classes the status badge is painted with.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::PAID => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
            self::PROCESSING => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::SHIPPED => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            self::DELIVERED => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::CANCELLED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            self::REFUNDED => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Returns the icon shown next to the label.
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => 'heroicon-s-clock',
            self::PAID => 'heroicon-s-check-circle',
            self::PROCESSING => 'heroicon-s-archive-box',
            self::SHIPPED => 'heroicon-s-truck',
            self::DELIVERED => 'heroicon-s-home-modern',
            self::COMPLETED => 'heroicon-s-sparkles',
            self::CANCELLED => 'heroicon-s-x-circle',
            self::REFUNDED => 'heroicon-s-arrow-uturn-left',
        };
    }

    /**
     * Tells whether the order is still waiting for its payment, so its stock is
     * only held and not yet sold.
     */
    public function awaitsPayment(): bool
    {
        return $this === self::PENDING_PAYMENT;
    }

    /**
     * Tells whether the order is paid for and its goods have left the warehouse.
     */
    public function isSettled(): bool
    {
        return in_array($this, self::settledCases(), true);
    }

    /**
     * Tells whether the order was taken back, so its stock has to be returned.
     */
    public function isUndone(): bool
    {
        return $this === self::CANCELLED || $this === self::REFUNDED;
    }

    /**
     * Returns the statuses whose orders count as revenue in the reports.
     *
     * @return array<int, self>
     */
    public static function settledCases(): array
    {
        return [self::PAID, self::PROCESSING, self::SHIPPED, self::DELIVERED, self::COMPLETED];
    }
}
