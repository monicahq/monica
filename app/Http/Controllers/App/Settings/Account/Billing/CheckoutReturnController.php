<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Actions\SettleOrder;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutReturnController extends Controller
{
    public function show(Request $request): RedirectResponse
    {
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId === '') {
            return redirect()->route('settings.billing.index');
        }

        try {
            $order = new SettleOrder(
                account: $request->user()->account,
                sessionId: $sessionId,
            )->execute();
        } catch (AccountAlreadyOwnsTheProduct) {
            return redirect()->route('settings.billing.index')
                ->withErrors(['offer' => __('That plan is not one this account can take up.')]);
        }

        return redirect()->route('settings.billing.index', [
            'checkout' => $order === null ? 'pending' : 'success',
        ]);
    }
}
