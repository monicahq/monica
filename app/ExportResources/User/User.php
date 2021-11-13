<?php

namespace App\ExportResources\User;

use App\Models\Contact\Contact;
use App\ExportResources\ExportResource;
use Illuminate\Http\Resources\MissingValue;

class User extends ExportResource
{
    protected $columns = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'google2fa_secret',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'locale',
        'metric',
        'fluid_container',
        'contacts_sort_order',
        'name_order',
        'dashboard_active_tab',
        'gifts_active_tab',
        'profile_active_tab',
        'timezone',
        'profile_new_life_event_badge_seen',
        'temperature_scale',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->currency !== null, [
                    'currency' => $this->currency->iso,
                ]),
                $this->mergeWhen($this->invited_by_user_id !== null, function () {
                    try {
                        $invited_by_user = Contact::where('account_id', $this->account_id)
                                                    ->findOrFail($this->invited_by_user_id);

                        return [
                            'invited_by_user' => $invited_by_user->uuid,
                        ];
                    } catch (\Exception $e) {
                        return new MissingValue();
                    }
                }),
                $this->mergeWhen($this->me !== null, function () {
                    return ['me_contact' => $this->me->uuid];
                }),
            ],
        ];
    }
}
