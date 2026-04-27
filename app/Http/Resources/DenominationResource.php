<?php

namespace App\Http\Resources;

use App\Models\Denomination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Denomination
 */
class DenominationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency?->code,
            'value' => $this->value,
            'quantity' => $this->quantity,
        ];
    }
}
