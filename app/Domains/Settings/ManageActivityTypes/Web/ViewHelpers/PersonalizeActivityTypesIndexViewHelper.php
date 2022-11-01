<?php

namespace App\Domains\Settings\ManageActivityTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\Activity;
use App\Models\ActivityType;

class PersonalizeActivityTypesIndexViewHelper
{
    public static function data(Account $account): array
    {
        $activityTypes = $account->activityTypes()
            ->with('activities')
            ->orderBy('label', 'asc')
            ->get();

        $collection = collect();
        foreach ($activityTypes as $type) {
            $collection->push(self::dtoActivityType($type));
        }

        return [
            'activity_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'activity_type_store' => route('settings.personalize.activity.type.store'),
            ],
        ];
    }

    public static function dtoActivityType(ActivityType $type): array
    {
        return [
            'id' => $type->id,
            'label' => $type->label,
            'activities' => $type->activities->map(function ($activity) use ($type) {
                return self::dtoActivity($type, $activity);
            }),
            'url' => [
                'store' => route('settings.personalize.activity.store', [
                    'activityType' => $type->id,
                ]),
                'update' => route('settings.personalize.activity.type.update', [
                    'activityType' => $type->id,
                ]),
                'destroy' => route('settings.personalize.activity.type.destroy', [
                    'activityType' => $type->id,
                ]),
            ],
        ];
    }

    public static function dtoActivity(ActivityType $type, Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'label' => $activity->label,
            'url' => [
                'update' => route('settings.personalize.activity.update', [
                    'activityType' => $type->id,
                    'activity' => $activity->id,
                ]),
                'destroy' => route('settings.personalize.activity.destroy', [
                    'activityType' => $type->id,
                    'activity' => $activity->id,
                ]),
            ],
        ];
    }
}
