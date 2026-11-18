<?php

namespace App\Domains\BlackBookd\ManageEncounters\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdEncounter;
use App\Models\Contact;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpdateBlackBookdEncounter extends BaseService implements ServiceInterface
{
    private BlackBookdEncounter $encounter;

    /** @var Collection<int, Contact> */
    private Collection $contacts;

    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'encounter_id' => 'required|uuid|exists:blackbookd_encounters,id',
            'occurred_at' => 'required|date',
            'location_label' => 'nullable|string|max:255',
            'how_met_category' => 'nullable|string|max:255',
            'how_met_app' => 'nullable|string|max:255',
            'how_met_detail' => 'nullable|string|max:65535',
            'protection_used' => 'nullable|array',
            'activity_tags' => 'nullable|array',
            'substances' => 'nullable|string|max:65535',
            'rating' => 'nullable|integer|between:1,5',
            'private_notes' => 'nullable|string|max:65535',
            'consent_notes' => 'nullable|string|max:65535',
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'uuid|exists:contacts,id',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): BlackBookdEncounter
    {
        $this->validateRules($data);
        $this->encounter = $this->vault->blackbookdEncounters()->findOrFail($data['encounter_id']);
        $this->contacts = collect($data['contact_ids'])
            ->map(fn (string $id): Contact => $this->vault->contacts()->findOrFail($id));

        $this->encounter->update([
            'occurred_at' => Carbon::parse($data['occurred_at']),
            'location_label' => $this->valueOrNull($data, 'location_label'),
            'how_met_category' => $this->valueOrNull($data, 'how_met_category'),
            'how_met_app' => $this->valueOrNull($data, 'how_met_app'),
            'how_met_detail' => $this->valueOrNull($data, 'how_met_detail'),
            'protection_used' => $data['protection_used'] ?? [],
            'activity_tags' => $data['activity_tags'] ?? [],
            'substances' => $this->valueOrNull($data, 'substances'),
            'rating' => $this->valueOrNull($data, 'rating'),
            'private_notes' => $this->valueOrNull($data, 'private_notes'),
            'consent_notes' => $this->valueOrNull($data, 'consent_notes'),
        ]);
        $this->encounter->contacts()->sync($this->contacts->pluck('id'));

        return $this->encounter->load('contacts');
    }
}