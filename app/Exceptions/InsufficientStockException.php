<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stock mutation would push a balance below zero.
 */
class InsufficientStockException extends RuntimeException
{
    //
}
