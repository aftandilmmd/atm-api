<?php

namespace App\Services;

final class BanknoteDispenser
{
    /**
     * @param  array<int, int>  $inventory
     * @return array<int, int>|null
     */
    public function dispense(array $inventory, int $amount): ?array
    {
        if ($amount <= 0) {
            return null;
        }

        $available = array_filter($inventory, fn ($q) => $q > 0);
        if (empty($available)) {
            return null;
        }

        $dp = array_fill(0, $amount + 1, PHP_INT_MAX);
        $choice = array_fill(0, $amount + 1, 0);
        $dp[0] = 0;

        foreach ($available as $denom => $maxCount) {
            for ($i = $amount; $i >= $denom; $i--) {
                for ($k = 1; $k <= $maxCount && $k * $denom <= $i; $k++) {
                    $prev = $i - $k * $denom;
                    if ($dp[$prev] !== PHP_INT_MAX && $dp[$i] > $dp[$prev] + $k) {
                        $dp[$i] = $dp[$prev] + $k;
                        $choice[$i] = $denom;
                    }
                }
            }
        }

        if ($dp[$amount] === PHP_INT_MAX) {
            return null;
        }

        $result = [];
        $remaining = $amount;
        while ($remaining > 0) {
            $d = $choice[$remaining];
            $result[$d] = ($result[$d] ?? 0) + 1;
            $remaining -= $d;
        }

        krsort($result);

        return $result;
    }
}
