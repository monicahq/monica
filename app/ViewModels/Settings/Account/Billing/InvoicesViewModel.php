<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class InvoicesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    /**
     * Every payment Stripe confirmed for this account, newest first.
     *
     * @return Collection<int, Payment>
     */
    public function payments(): Collection
    {
        return once(fn (): Collection => $this->user->account->payments()->get());
    }

    /**
     * What the payment was for. The kind stored at payment time is what a retagged price cannot
     * rewrite, and the catalogue is only asked when the row carries none.
     */
    public function paymentLabel(Payment $payment): string
    {
        $kind = $payment->kind ?? StripeCatalogue::kindOfPayment($payment);

        if ($kind === null) {
            return __('Subscription');
        }

        return __($kind->label());
    }
}
