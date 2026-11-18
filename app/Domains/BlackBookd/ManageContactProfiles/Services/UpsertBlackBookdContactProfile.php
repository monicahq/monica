<?php

namespace App\Domains\BlackBookd\ManageContactProfiles\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdContactProfile;
use App\Services\BaseService;
use Carbon\Carbon;

class UpsertBlackBookdContactProfile extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'hiv_status' => 'nullable|string|max:255',
            'on_prep' => 'nullable|boolean',
            'last_test_date' => 'nullable|date_format:Y-m-d',
            'kinks' => 'nullable|string|max:65535',
            'boundaries' => 'nullable|string|max:65535',
            'relationship_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    public function execute(array $data): BlackBookdContactProfile
    {
        $this->validateRules($data);

        $profile = BlackBookdContactProfile::updateOrCreate(
            ['contact_id' => $data['contact_id']],
            [
                'hiv_status' => $this->valueOrNull($data, 'hiv_status'),
                'on_prep' => array_key_exists('on_prep', $data) ? $data['on_prep'] : null,
                'last_test_date' => $this->valueOrNull($data, 'last_test_date'),
                'kinks' => $this->valueOrNull($data, 'kinks'),
                'boundaries' => $this->valueOrNull($data, 'boundaries'),
                'relationship_type' => $this->valueOrNull($data, 'relationship_type'),
                'notes' => $this->valueOrNull($data, 'notes'),
            ]
        );

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $profile;
    }
}