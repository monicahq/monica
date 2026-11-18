<?php

namespace App\Domains\BlackBookd\ManageHealthLog\Services;

use App\Domains\Contact\ManageReminders\Services\CreateContactReminder;
use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdHealthLogEntry;
use App\Models\ContactReminder;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateBlackBookdHealthLogEntry extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'entry_type' => 'required|string|max:255',
            'occurred_on' => 'required|date_format:Y-m-d',
            'payload' => 'nullable|array',
            'notes' => 'nullable|string|max:65535',
            'schedule_sti_reminder' => 'nullable|boolean',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): BlackBookdHealthLogEntry
    {
        $this->validateRules($data);

        $entry = BlackBookdHealthLogEntry::create([
            'vault_id' => $data['vault_id'],
            'entry_type' => $data['entry_type'],
            'occurred_on' => $data['occurred_on'],
            'payload' => $data['payload'] ?? null,
            'notes' => $this->valueOrNull($data, 'notes'),
        ]);

        if (
            $data['entry_type'] === BlackBookdHealthLogEntry::TYPE_STI_TEST
            && ($data['schedule_sti_reminder'] ?? true)
        ) {
            $self = $this->author->getContactInVault($this->vault);
            if ($self !== null) {
                $next = Carbon::parse($data['occurred_on'])->addDays(90);
                (new CreateContactReminder)->execute([
                    'account_id' => $data['account_id'],
                    'vault_id' => $data['vault_id'],
                    'author_id' => $data['author_id'],
                    'contact_id' => $self->id,
                    'label' => 'BlackBookd: STI test due (90-day cadence)',
                    'day' => $next->day,
                    'month' => $next->month,
                    'year' => $next->year,
                    'type' => ContactReminder::TYPE_ONE_TIME,
                    'frequency_number' => null,
                ]);
            }
        }

        return $entry;
    }
}