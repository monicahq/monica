<?php

namespace App\Settings\ManagePronouns\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\Pronoun;
use App\Models\User;
use App\Services\BaseService;

class UpdatePronoun extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'pronoun_id' => 'required|integer|exists:pronouns,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     * @return Pronoun
     */
    public function execute(array $data): Pronoun
    {
        $this->validateRules($data);

        $pronoun = Pronoun::where('account_id', $data['account_id'])
            ->findOrFail($data['pronoun_id']);

        $pronoun->name = $data['name'];
        $pronoun->save();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'pronoun_updated',
            'objects' => json_encode([
                'pronoun_name' => $pronoun->name,
            ]),
        ])->onQueue('low');

        return $pronoun;
    }
}
