<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\OfferKindEnum;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\StripeOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

class BillingFigures
{
    /** @return array{lifetime: int, granted: int, monthly: int, yearly: int, kindNotKnown: int, none: int} */
    public function access(): array
    {
        $lifetime = Account::query()->whereHas('orders', $this->paidOrder(...))->count();
        $granted = Account::query()->whereDoesntHave('orders', $this->paidOrder(...))->whereNotNull('access_granted_at')->count();

        $subscriptionIds = Cashier::$subscriptionModel::query()
            ->where($this->validSubscription(...))
            ->whereIn('account_id', Account::query()->select('id')->whereDoesntHave('orders', $this->paidOrder(...))->whereNull('access_granted_at'))
            ->pluck('stripe_id', 'account_id');

        $kinds = $this->kindsOf($subscriptionIds->values()->all());
        $counts = ['lifetime' => $lifetime, 'granted' => $granted, 'monthly' => 0, 'yearly' => 0, 'kindNotKnown' => 0, 'none' => 0];

        foreach ($subscriptionIds as $subscriptionId) {
            $key = match ($kinds[$subscriptionId] ?? null) {
                OfferKindEnum::Monthly => 'monthly',
                OfferKindEnum::Yearly => 'yearly',
                default => 'kindNotKnown',
            };

            $counts[$key]++;
        }

        $counts['none'] = Account::query()->count() - $lifetime - $granted - $subscriptionIds->count();

        return $counts;
    }

    /** @return array{active: int, cancelledButRunning: int, pastDue: int} */
    public function subscriptions(): array
    {
        return [
            'active' => Cashier::$subscriptionModel::query()->active()->whereNull('ends_at')->where('stripe_status', '!=', 'past_due')->count(),
            'cancelledButRunning' => Cashier::$subscriptionModel::query()->onGracePeriod()->count(),
            'pastDue' => Cashier::$subscriptionModel::query()->pastDue()->whereNull('ends_at')->count(),
        ];
    }

    /** @return list<array{month: CarbonImmutable, rows: list<array{kind: ?OfferKindEnum, currency: string, count: int, total: int, tax: int}>}> */
    public function payments(): array
    {
        $thisMonth = now()->toImmutable()->startOfMonth();
        $lastMonth = $thisMonth->subMonthNoOverflow();

        $payments = Payment::query()
            ->where('billed_at', '>=', $lastMonth)
            ->where('billed_at', '<', $thisMonth->addMonthNoOverflow())
            ->orderBy('currency')
            ->get(['kind', 'currency', 'total', 'tax', 'billed_at']);

        $months = [];

        foreach ([$thisMonth, $lastMonth] as $month) {
            $rows = [];

            foreach ($payments as $payment) {
                if (! $payment->billed_at->isSameMonth($month)) {
                    continue;
                }

                $key = "{$payment->currency}|{$payment->kind?->value}";
                $rows[$key] ??= ['kind' => $payment->kind, 'currency' => $payment->currency, 'count' => 0, 'total' => 0, 'tax' => 0];
                $rows[$key]['count']++;
                $rows[$key]['total'] += $payment->total;
                $rows[$key]['tax'] += $payment->tax;
            }

            $months[] = ['month' => $month, 'rows' => array_values($rows)];
        }

        return $months;
    }

    public function refunds(): int
    {
        return $this->eventsOfTheLastThirtyDays('charge.refunded');
    }

    public function disputes(): int
    {
        return $this->eventsOfTheLastThirtyDays('charge.dispute.created');
    }

    public function hasAnyPayment(): bool
    {
        return Payment::query()->exists();
    }

    /** @param  Builder<StripeOrder>  $query */
    private function paidOrder(Builder $query): void
    {
        $query->where('stripe_status', StripeOrder::PAID);
    }

    /** @param  Builder<Subscription>  $query */
    private function validSubscription(Builder $query): void
    {
        $query->where('type', 'default')->where(fn (Builder $valid) => $valid->active()->orWhere(fn (Builder $onTrial) => $onTrial->onTrial()));
    }

    /**
     * @param  list<string>  $subscriptionIds
     * @return array<string, ?OfferKindEnum>
     */
    private function kindsOf(array $subscriptionIds): array
    {
        $kinds = [];

        foreach (Payment::query()->whereIn('stripe_subscription_id', $subscriptionIds)->orderBy('billed_at')->get(['stripe_subscription_id', 'kind']) as $payment) {
            $kinds[(string) $payment->stripe_subscription_id] = $payment->kind;
        }

        return $kinds;
    }

    private function eventsOfTheLastThirtyDays(string $type): int
    {
        return StripeEvent::query()
            ->where('type', $type)
            ->where('created_at', '>=', now()->subDays(StripeEvent::RETENTION_IN_DAYS))
            ->count();
    }
}
