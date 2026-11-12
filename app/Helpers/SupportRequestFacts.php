<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\SupportRequest;

/**
 * What the administrators are told about the account behind a support request: enough to answer it,
 * and nothing from inside a vault.
 */
class SupportRequestFacts
{
    /** @return list<array{label: string, value: string}> */
    public static function of(SupportRequest $supportRequest): array
    {
        $account = $supportRequest->account;
        $locale = $supportRequest->locale;
        $vaults = trans_choice(':count vault|:count vaults', $account->vaults()->count());
        $contacts = trans_choice(':count contact|:count contacts', $account->contacts()->count());

        return [
            ['label' => __('Account'), 'value' => "#{$supportRequest->account_id}"],
            ['label' => __('Access'), 'value' => __($account->accessKind()?->label() ?? 'Free plan')],
            ['label' => __('Language'), 'value' => AvailableLocales::has($locale) ? (string) AvailableLocales::details($locale)['label'] : $locale],
            ['label' => __('Version'), 'value' => $supportRequest->application_version],
            ['label' => __('Content'), 'value' => "{$vaults}, {$contacts}"],
            ['label' => __('Signed up'), 'value' => $account->created_at->isoFormat('LL')],
        ];
    }
}
