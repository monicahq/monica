<?php

namespace App\Domains\Settings\ManageGiftOccasions\Web\ViewHelpers;

use App\Models\Account;
use App\Models\GiftOccasion;

class PersonalizeGiftOccasionViewHelper
{
    public static function data(Account $account): array
    {
        $giftOccasions = $account->giftOccasions()
            ->orderBy('position', 'asc')
            ->get();

        $collection = collect();
        foreach ($giftOccasions as $giftOccasion) {
            $collection->push(self::dto($giftOccasion));
        }

        return [
            'gift_occasions' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'store' => route('settings.personalize.gift_occasions.store'),
            ],
        ];
    }

    public static function dto(GiftOccasion $giftOccasion): array
    {
        return [
            'id' => $giftOccasion->id,
            'label' => $giftOccasion->label,
            'position' => $giftOccasion->position,
            'url' => [
                'position' => route('settings.personalize.gift_occasions.order.update', [
                    'giftOccasion' => $giftOccasion->id,
                ]),
                'update' => route('settings.personalize.gift_occasions.update', [
                    'giftOccasion' => $giftOccasion->id,
                ]),
                'destroy' => route('settings.personalize.gift_occasions.destroy', [
                    'giftOccasion' => $giftOccasion->id,
                ]),
            ],
        ];
    }
}
