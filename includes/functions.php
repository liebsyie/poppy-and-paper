<?php
declare(strict_types=1);

/**
 * Escape values before placing them into HTML.
 * Prevents XSS by converting special characters to HTML entities.
 */
function e(mixed $value): string
{
    // Convert non-string scalar values (int, float, bool) safely to string
    if (!is_string($value)) {
        if ($value === null) {
            $value = '';
        } elseif (is_scalar($value)) {
            $value = (string) $value;
        } else {
            return ''; // Arrays/objects can't be safely echoed
        }
    }
    
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format a numeric amount as Philippine pesos.
 * Falls back to ₱0.00 if the value is invalid or non-numeric.
 */
function formatPeso(int|float|string|null $amount): string
{
    // Ensure we're working with a valid number
    if (!is_numeric($amount)) {
        $amount = 0;
    }

    return '₱' . number_format((float) $amount, 2);
}