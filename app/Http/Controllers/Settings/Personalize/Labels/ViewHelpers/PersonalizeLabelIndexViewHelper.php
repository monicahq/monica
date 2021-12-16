<?php

namespace App\Http\Controllers\Settings\Personalize\Labels\ViewHelpers;

use App\Models\Label;
use App\Models\Account;

class PersonalizeLabelIndexViewHelper
{
    public static function data(Account $account): array
    {
        $labels = $account->labels()
            ->withCount('contacts')
            ->orderBy('name', 'asc')
            ->get();

        $collection = collect();
        foreach ($labels as $label) {
            $collection->push(self::dtoLabel($label));
        }

        return [
            'labels' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'label_store' => route('settings.personalize.label.store'),
            ],
        ];
    }

    public static function dtoLabel(Label $label): array
    {
        return [
            'id' => $label->id,
            'name' => $label->name,
            'count' => $label->contacts_count,
            'url' => [
                'update' => route('settings.personalize.label.update', [
                    'label' => $label->id,
                ]),
                'destroy' => route('settings.personalize.label.destroy', [
                    'label' => $label->id,
                ]),
            ],
        ];
    }
}
