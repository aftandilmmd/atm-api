<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['currency_id', 'value', 'quantity'])]
class Denomination extends Model
{
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
