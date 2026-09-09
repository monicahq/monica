<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingCurrencyController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', Rule::in(StripeCatalogue::currencies())],
        ]);

        $request->session()->put('billing.currency', $validated['currency']);

        return redirect()->route('settings.billing.index');
    }
}
