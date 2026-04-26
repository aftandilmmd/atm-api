<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_name' => $this->owner_name,
            'currency' => $this->currency?->code,
            'balance' => $this->balance,
            'balance_formatted' => format_money($this->balance, $this->currency?->code ?? 'AZN'),
        ];
    }
}
