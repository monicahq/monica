<?php

namespace App\Domains\Settings\ManageGiftStates\Web\ViewHelpers;

use App\Models\Account;
use App\Models\GiftState;

class PersonalizeGiftStateViewHelper
{
    public static function data(Account $account): array
    {
        $giftStates = $account->giftStates()
            ->orderBy('position', 'asc')
            ->get();

        $collection = collect();
        foreach ($giftStates as $giftState) {
            $collection->push(self::dto($giftState));
        }

        return [
            'gift_states' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'store' => route('settings.personalize.gift_states.store'),
            ],
        ];
    }

    public static function dto(GiftState $giftState): array
    {
        return [
            'id' => $giftState->id,
            'label' => $giftState->label,
            'position' => $giftState->position,
            'url' => [
                'position' => route('settings.personalize.gift_states.order.update', [
                    'giftState' => $giftState->id,
                ]),
                'update' => route('settings.personalize.gift_states.update', [
                    'giftState' => $giftState->id,
                ]),
                'destroy' => route('settings.personalize.gift_states.destroy', [
                    'giftState' => $giftState->id,
                ]),
            ],
        ];
    }
}
