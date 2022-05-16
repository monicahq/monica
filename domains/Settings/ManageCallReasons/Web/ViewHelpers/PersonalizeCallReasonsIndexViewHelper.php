<?php

namespace App\Settings\ManageCallReasons\Web\ViewHelpers;

use App\Models\Account;
use App\Models\CallReason;
use App\Models\CallReasonType;

class PersonalizeCallReasonsIndexViewHelper
{
    public static function data(Account $account): array
    {
        $callReasonTypes = $account->callReasonTypes()
            ->with('callReasons')
            ->orderBy('label', 'asc')
            ->get();

        $collection = collect();
        foreach ($callReasonTypes as $type) {
            $collection->push(self::dtoReasonType($type));
        }

        return [
            'call_reason_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'call_reason_type_store' => route('settings.personalize.call_reasons.type.store'),
            ],
        ];
    }

    public static function dtoReasonType(CallReasonType $type): array
    {
        return [
            'id' => $type->id,
            'label' => $type->label,
            'reasons' => $type->callReasons->map(function ($reason) use ($type) {
                return self::dtoReason($type, $reason);
            }),
            'url' => [
                'store' => route('settings.personalize.call_reasons.store', [
                    'callReasonType' => $type->id,
                ]),
                'update' => route('settings.personalize.call_reasons.type.update', [
                    'callReasonType' => $type->id,
                ]),
                'destroy' => route('settings.personalize.call_reasons.type.destroy', [
                    'callReasonType' => $type->id,
                ]),
            ],
        ];
    }

    public static function dtoReason(CallReasonType $type, CallReason $reason): array
    {
        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'url' => [
                'update' => route('settings.personalize.call_reasons.update', [
                    'callReasonType' => $type->id,
                    'reason' => $reason->id,
                ]),
                'destroy' => route('settings.personalize.call_reasons.destroy', [
                    'callReasonType' => $type->id,
                    'reason' => $reason->id,
                ]),
            ],
        ];
    }
}
