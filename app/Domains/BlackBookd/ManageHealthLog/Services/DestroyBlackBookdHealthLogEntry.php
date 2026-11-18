<?php

namespace App\Domains\BlackBookd\ManageHealthLog\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyBlackBookdHealthLogEntry extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'entry_id' => 'required|uuid|exists:blackbookd_health_log_entries,id',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): void
    {
        $this->validateRules($data);
        $this->vault->blackbookdHealthLogEntries()->findOrFail($data['entry_id'])->delete();
    }
}