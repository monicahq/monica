<?php

namespace App\Domains\Settings\ManagePetCategories\Web\ViewHelpers;

use App\Models\Account;
use App\Models\PetCategory;

class PersonalizePetCategoriesIndexViewHelper
{
    public static function data(Account $account): array
    {
        $categories = $account->petCategories()
            ->get()
            ->sortByCollator('name')
            ->map(fn (PetCategory $petCategory) => self::dtoPetCategory($petCategory));

        return [
            'pet_categories' => $categories,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'pet_category_store' => route('settings.personalize.pet_category.store'),
            ],
        ];
    }

    public static function dtoPetCategory(PetCategory $petCategory): array
    {
        return [
            'id' => $petCategory->id,
            'name' => $petCategory->name,
            'url' => [
                'update' => route('settings.personalize.pet_category.update', [
                    'petCategory' => $petCategory->id,
                ]),
                'destroy' => route('settings.personalize.pet_category.destroy', [
                    'petCategory' => $petCategory->id,
                ]),
            ],
        ];
    }
}
