<?php

namespace App\Settings\ManageLifeEventCategories\Web\ViewHelpers;

use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;

class PersonalizeLifeEventCategoriesViewHelper
{
    public static function data(Account $account): array
    {
        $lifeEventCategories = $account->lifeEventCategories()
            ->with('lifeEventTypes')
            ->orderBy('label', 'asc')
            ->get();

        $collection = collect();
        foreach ($lifeEventCategories as $category) {
            $collection->push(self::dtoLifeEventCategory($category));
        }

        return [
            'life_event_categories' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'store' => route('settings.personalize.life_event_categories.store'),
            ],
        ];
    }

    public static function dtoLifeEventCategory(LifeEventCategory $category): array
    {
        $lifeEventTypesCollection = $category->lifeEventTypes()
            ->orderBy('position', 'asc')
            ->get();

        return [
            'id' => $category->id,
            'label' => $category->label,
            'can_be_deleted' => $category->can_be_deleted,
            'type' => $category->type,
            'life_event_types' => $lifeEventTypesCollection->map(function ($type) use ($category) {
                return self::dtoType($category, $type);
            }),
            'url' => [
                'store' => route('settings.personalize.life_event_types.store', [
                    'lifeEventCategory' => $category->id,
                ]),
                'update' => route('settings.personalize.life_event_categories.update', [
                    'lifeEventCategory' => $category->id,
                ]),
                'destroy' => route('settings.personalize.life_event_categories.destroy', [
                    'lifeEventCategory' => $category->id,
                ]),
            ],
        ];
    }

    public static function dtoType(LifeEventCategory $category, LifeEventType $type): array
    {
        return [
            'id' => $type->id,
            'label' => $type->label,
            'can_be_deleted' => $type->can_be_deleted,
            'type' => $type->type,
            'position' => $type->position,
            'url' => [
                'position' => route('settings.personalize.life_event_types.order.update', [
                    'lifeEventCategory' => $category->id,
                    'lifeEventType' => $type->id,
                ]),
                'update' => route('settings.personalize.life_event_types.update', [
                    'lifeEventCategory' => $category->id,
                    'lifeEventType' => $type->id,
                ]),
                'destroy' => route('settings.personalize.life_event_types.destroy', [
                    'lifeEventCategory' => $category->id,
                    'lifeEventType' => $type->id,
                ]),
            ],
        ];
    }
}
