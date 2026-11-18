<?php

namespace App\Domains\BlackBookd\ManagePartnerNotification\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdEncounter;
use App\Models\Contact;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetPartnerNotificationContacts extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'positive_test_date' => 'required|date_format:Y-m-d',
            'lookback_days' => 'nullable|integer|between:1,730',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    /**
     * @return Collection<int, array{contact: Contact, encounter_count: int, last_encounter_at: string|null}>
     */
    public function execute(array $data): Collection
    {
        $this->validateRules($data);
        $testDate = Carbon::parse($data['positive_test_date'])->endOfDay();
        $lookback = $data['lookback_days'] ?? $this->author->account->blackbookd_partner_lookback_days;
        $windowStart = $testDate->copy()->subDays($lookback)->startOfDay();

        $encounters = $this->vault->blackbookdEncounters()
            ->whereBetween('occurred_at', [$windowStart, $testDate])
            ->with('contacts')
            ->get();

        $grouped = [];
        foreach ($encounters as $encounter) {
            /** @var BlackBookdEncounter $encounter */
            foreach ($encounter->contacts as $contact) {
                if (! isset($grouped[$contact->id])) {
                    $grouped[$contact->id] = [
                        'contact' => $contact,
                        'encounter_count' => 0,
                        'last_encounter_at' => null,
                    ];
                }
                $grouped[$contact->id]['encounter_count']++;
                $occurred = $encounter->occurred_at->toIso8601String();
                if ($grouped[$contact->id]['last_encounter_at'] === null || $occurred > $grouped[$contact->id]['last_encounter_at']) {
                    $grouped[$contact->id]['last_encounter_at'] = $occurred;
                }
            }
        }

        return collect(array_values($grouped));
    }
}