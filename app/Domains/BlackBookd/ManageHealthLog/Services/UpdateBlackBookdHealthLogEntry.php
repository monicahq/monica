<?php

namespace App\Domains\BlackBookd\ManageHealthLog\Services;

use App\Interfaces\ServiceInterface;
use App\Models\BlackBookdHealthLogEntry;
use App\Services\BaseService;

class UpdateBlackBookdHealthLogEntry extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'entry_id' => 'required|uuid|exists:blackbookd_health_log_entries,id',
            'entry_type' => 'required|string|max:255',
            'occurred_on' => 'required|date_format:Y-m-d',
            'payload' => 'nullable|array',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): BlackBookdHealthLogEntry
    {
        $this->validateRules($data);
        $entry = $this->vault->blackbookdHealthLogEntries()->findOrFail($data['entry_id']);
        $entry->update([
            'entry_type' => $data['entry_type'],
            'occurred_on' => $data['occurred_on'],
            'payload' => $data['payload'] ?? null,
            'notes' => $this->valueOrNull($data, 'notes'),
        ]);

        return $entry;
    }
}