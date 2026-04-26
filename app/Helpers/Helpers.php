<?php

if (! function_exists('format_money')) {
    function format_money(int $amount, string $currency = 'AZN'): string
    {
        return number_format($amount / 100, 2) . ' ' . $currency;
    }
}