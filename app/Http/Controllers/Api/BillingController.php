<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingResource;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function show(Request $request): BillingResource
    {
        return new BillingResource($request->user()->account);
    }
}
