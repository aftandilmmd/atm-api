<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AccountResource::collection(Account::with('currency')->paginate(20));
    }

    public function show(Account $account): AccountResource
    {
        return new AccountResource($account->load('currency'));
    }
}