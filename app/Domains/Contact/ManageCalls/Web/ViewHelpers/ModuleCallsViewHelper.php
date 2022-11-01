<?php

namespace App\Domains\Contact\ManageCalls\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Call;
use App\Models\Contact;
use App\Models\User;

class ModuleCallsViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $callsCollection = $contact->calls()
            ->orderBy('called_at', 'desc')
            ->get()
            ->map(function ($call) use ($contact, $user) {
                return self::dto($contact, $call, $user);
            });

        $callReasonTypes = $contact->vault->account->callReasonTypes()
            ->with('callReasons')
            ->get();

        $callReasonTypesCollection = collect();
        foreach ($callReasonTypes as $callReasonType) {
            $callReasons = $callReasonType->callReasons;

            $callReasonsCollection = collect();
            foreach ($callReasons as $callReason) {
                $callReasonsCollection->push([
                    'id' => $callReason->id,
                    'label' => $callReason->label,
                ]);
            }

            $callReasonTypesCollection->push([
                'id' => $callReasonType->id,
                'label' => $callReasonType->label,
                'reasons' => $callReasonsCollection,
            ]);
        }

        $emotions = $contact->vault->account->emotions()->get();
        $emotionsCollection = $emotions->map(function ($emotion) {
            return [
                'id' => $emotion->id,
                'name' => $emotion->name,
                'type' => $emotion->type,
            ];
        });

        return [
            'contact_name' => $contact->name,
            'calls' => $callsCollection,
            'emotions' => $emotionsCollection,
            'call_reason_types' => $callReasonTypesCollection,
            'url' => [
                'store' => route('contact.call.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Call $call, User $user): array
    {
        return [
            'id' => $call->id,
            'called_at' => DateHelper::format($call->called_at, $user),
            'duration' => $call->duration,
            'description' => $call->description,
            'who_initiated' => $call->who_initiated,
            'type' => $call->type,
            'answered' => $call->answered,
            'emotion' => $call->emotion ? [
                'id' => $call->emotion->id,
                'name' => $call->emotion->name,
                'type' => $call->emotion->type,
            ] : null,
            'reason' => $call->callReason ? [
                'id' => $call->callReason->id,
                'label' => $call->callReason->label,
            ] : null,
            'url' => [
                'update' => route('contact.call.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'call' => $call->id,
                ]),
                'destroy' => route('contact.call.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'call' => $call->id,
                ]),
            ],
        ];
    }
}
