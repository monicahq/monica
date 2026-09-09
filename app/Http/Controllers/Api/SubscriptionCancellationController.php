<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ResumeSubscription;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\BillingResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionCancellationController extends Controller
{
    public function destroy(Request $request): BillingResource
    {
        try {
            new ResumeSubscription(user: $request->user())->execute();
        } catch (AccountHasNoSubscription|SubscriptionCannotBeChanged $e) {
            throw ValidationException::withMessages(['subscription' => $e->getMessage()]);
        }

        return new BillingResource($request->user()->account->refresh());
    }
}
