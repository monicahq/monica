<?php

namespace App\Domains\Settings\ManagePronouns\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyPronoun extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'pronoun_id' => 'required|integer|exists:pronouns,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Destroy a pronoun.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $pronoun = $this->account()->pronouns()
            ->findOrFail($data['pronoun_id']);

        $pronoun->delete();
    }
}
