<?php

namespace App\Domains\BlackBookd\ManageEncounters\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyBlackBookdEncounter extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'encounter_id' => 'required|uuid|exists:blackbookd_encounters,id',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'vault_must_belong_to_account', 'author_must_be_vault_editor'];
    }

    public function execute(array $data): void
    {
        $this->validateRules($data);
        $this->vault->blackbookdEncounters()->findOrFail($data['encounter_id'])->delete();
    }
}