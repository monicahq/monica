<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Billing\BillingViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.billing.index', [
            'viewModel' => new BillingViewModel(
                user: $request->user(),
                returnedFromCheckout: in_array($request->string('checkout')->toString(), ['success', 'pending'], true),
                currency: $request->session()->get('billing.currency'),
            ),
        ]);
    }
}
