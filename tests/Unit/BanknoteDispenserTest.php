<?php

use App\Services\BanknoteDispenser;

test('standart denomination üçün minimum əskinaz', function () {
    $plan = (new BanknoteDispenser)->dispense([10000 => 5, 2000 => 10, 500 => 20], 12500);
    expect($plan)->toBe([10000 => 1, 2000 => 1, 500 => 1]);
});

test('stok limitini nəzərə alır', function () {
    $plan = (new BanknoteDispenser)->dispense([100 => 1, 50 => 10], 200);
    expect($plan)->toBe([100 => 1, 50 => 2]);
});

test('mümkün olmadıqda null qaytarır', function () {
    expect((new BanknoteDispenser)->dispense([100 => 1, 50 => 1], 200))->toBeNull();
});

test('non-canonical denomination üçün düzgün işləyir', function () {
    // greedy 600+200 yapamaz, DP 400+400 bulur
    $plan = (new BanknoteDispenser)->dispense([600 => 5, 400 => 5, 300 => 5], 800);
    expect($plan)->toBe([400 => 2]);
});

test('boş input üçün null', function () {
    $s = new BanknoteDispenser;
    expect($s->dispense([], 100))->toBeNull();
    expect($s->dispense([100 => 1], 0))->toBeNull();
    expect($s->dispense([100 => 1], -50))->toBeNull();
});