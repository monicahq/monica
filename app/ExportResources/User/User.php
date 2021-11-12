<?php

namespace App\ExportResources\User;

use App\Models\Contact\Contact;
use App\Services\Account\Settings\ExportResource;

class User extends ExportResource
{
    protected $columns = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'google2fa_secret',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'admin',
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
        $invited_by_user = Contact::where('account_id', $this->account_id)->find($this->invited_by_user_id);

        return  [
            'properties' => [
                'currency' => $this->currency !== null ? $this->currency->iso : null,
                'invited_by_user' => $invited_by_user !== null ? $invited_by_user->uuid : null,
                'me_contact' => $this->me !== null ? $this->me->uuid : null,
            ],
        ];
    }
}
