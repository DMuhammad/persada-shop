<?php

namespace App\Enums;

/**
 * Reasons a stock balance changes. Most types carry a fixed direction, while a
 * correction takes its direction from the balance it lands on.
 */
enum StockMovementType: int
{
    case OPENING_BALANCE = 0;
    case PURCHASE = 1;
    case SALE = 2;
    case SALES_RETURN = 3;
    case ADJUSTMENT = 4;

    /**
     * Returns the sign this type applies to a stock balance, or zero when the
     * sign comes from the submitted balance instead.
     */
    public function direction(): int
    {
        return match ($this) {
            self::SALE => -1,
            self::ADJUSTMENT => 0,
            default => 1,
        };
    }

    /**
     * Tells whether this type is recorded as a counted balance rather than an
     * amount to add or subtract.
     */
    public function isAdjustment(): bool
    {
        return $this->direction() === 0;
    }

    /**
     * Returns the label shown in the dashboard.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPENING_BALANCE => 'Saldo Awal',
            self::PURCHASE => 'Stok Masuk',
            self::SALE => 'Penjualan',
            self::SALES_RETURN => 'Pengembalian',
            self::ADJUSTMENT => 'Koreksi Stok',
        };
    }

    /**
     * Returns the types an admin may submit through the dashboard form. The
     * remaining types are only written by the order flow.
     *
     * @return array<int, self>
     */
    public static function manualCases(): array
    {
        return [self::PURCHASE, self::ADJUSTMENT];
    }
}
