<?php

namespace App\Domains\Settings\ManagePronouns\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Pronoun;
use App\Services\BaseService;

class UpdatePronoun extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
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
     * Update a gender.
     */
    public function execute(array $data): Pronoun
    {
        $this->validateRules($data);

        $pronoun = $this->account()->pronouns()
            ->findOrFail($data['pronoun_id']);

        $pronoun->name = $data['name'];
        $pronoun->save();

        return $pronoun;
    }
}
