<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Billing\InvoicesViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.billing.invoices', [
            'viewModel' => new InvoicesViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function show(Request $request, string $payment): RedirectResponse
    {
        $payment = $request->user()->account->payments()
            ->where('stripe_id', $payment)
            ->firstOrFail();

        abort_if($payment->hosted_invoice_url === null, 404);

        return redirect()->away($payment->hosted_invoice_url);
    }
}
