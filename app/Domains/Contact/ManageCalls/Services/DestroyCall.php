<?php

namespace App\Domains\Contact\ManageCalls\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Call;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyCall extends BaseService implements ServiceInterface
{
    private Call $call;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'call_id' => 'required|integer|exists:calls,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Destroy a call.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->call = $this->contact->calls()
            ->findOrFail($data['call_id']);

        $this->call->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
