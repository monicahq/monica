<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\User;

/**
 * What the strip offering full access shows: how many people and vaults the
 * account holds against what the free plan allows, and how close each is.
 *
 * The layouts build it only for a user who is offered full access, which is an
 * account on the free plan, so both limits are always a number here.
 */
class FullAccessOfferViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<array{label: string, used: int, limit: int, percent: int, tone: string, description: string}> */
    public function meters(): array
    {
        $account = $this->user->account;

        return [
            $this->meter(__('People'), $account->contactCount(), (int) $account->contactLimit()),
            $this->meter(__('Vaults'), $account->vaults()->count(), (int) $account->vaultLimit()),
        ];
    }

    /** @return array{label: string, used: int, limit: int, percent: int, tone: string, description: string} */
    private function meter(string $label, int $used, int $limit): array
    {
        $percent = $limit === 0 ? 100 : min(100, (int) round($used / $limit * 100));

        $tone = match (true) {
            $limit === 0, $used >= $limit => 'danger',
            $used * 5 >= $limit * 4 => 'warning',
            default => 'neutral',
        };

        $description = match ($tone) {
            'danger' => __(':used of :limit, the limit is reached', ['used' => $used, 'limit' => $limit]),
            'warning' => __(':used of :limit, close to the limit', ['used' => $used, 'limit' => $limit]),
            default => __(':used of :limit', ['used' => $used, 'limit' => $limit]),
        };

        return ['label' => $label, 'used' => $used, 'limit' => $limit, 'percent' => $percent, 'tone' => $tone, 'description' => $description];
    }
}
