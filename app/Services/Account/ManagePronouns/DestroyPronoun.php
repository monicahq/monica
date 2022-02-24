<?php

namespace App\Services\Account\ManagePronouns;

use App\Models\User;
use App\Models\Pronoun;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class DestroyPronoun extends BaseService implements ServiceInterface
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
     * Destroy a pronoun.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $pronoun = Pronoun::where('account_id', $data['account_id'])
            ->findOrFail($data['pronoun_id']);

        $pronoun->delete();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'pronoun_destroyed',
            'objects' => json_encode([
                'gender_name' => $pronoun->name,
            ]),
        ])->onQueue('low');
    }
}
