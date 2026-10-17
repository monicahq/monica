<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Billing;

use App\Helpers\PageSize;
use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoicesViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /**
     * One page of the payments Stripe confirmed for this account, newest first.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function payments(): LengthAwarePaginator
    {
        return PageSize::paginate($this->user->account->payments(), $this->size);
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
