<?php

namespace App\Http\Controllers;

use App\Actions\UpdateDenominationAction;
use App\Http\Requests\UpdateDenominationRequest;
use App\Http\Resources\DenominationResource;
use App\Models\Denomination;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DenominationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DenominationResource::collection(
            Denomination::with('currency')->orderBy('currency_id')->orderByDesc('value')->get()
        );
    }

    public function update(UpdateDenominationRequest $request, Denomination $denomination, UpdateDenominationAction $action): DenominationResource
    {
        $updated = $action->execute(
            $denomination,
            $request->integer('quantity'),
            $request->ip() ?? '0.0.0.0',
        );

        return new DenominationResource($updated);
    }
}
