<?php

namespace App\Services\Account\ManagePronouns;

use App\Models\User;
use App\Models\Pronoun;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreatePronoun extends BaseService implements ServiceInterface
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
     * Create a pronoun.
     *
     * @param  array  $data
     * @return Pronoun
     */
    public function execute(array $data): Pronoun
    {
        $this->validateRules($data);

        $pronoun = Pronoun::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'pronoun_created',
            'objects' => json_encode([
                'pronoun_name' => $pronoun->name,
            ]),
        ])->onQueue('low');

        return $pronoun;
    }
}
