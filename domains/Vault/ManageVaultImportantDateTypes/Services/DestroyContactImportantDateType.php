<?php

namespace App\Vault\ManageVaultImportantDateTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\ContactImportantDateType;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Validation\ValidationException;

class DestroyContactImportantDateType extends BaseService implements ServiceInterface
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
            'vault_id' => 'required|integer|exists:vaults,id',
            'contact_important_date_type_id' => 'required|integer|exists:contact_important_date_types,id',
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
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Destroy a contact important date type.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = ContactImportantDateType::where('vault_id', $data['vault_id'])
            ->findOrFail($data['contact_important_date_type_id']);

        if (! $type->can_be_deleted) {
            throw new ValidationException();
        }

        $type->delete();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_important_date_type_destroyed',
            'objects' => json_encode([
                'type_label' => $type->label,
            ]),
        ])->onQueue('low');
    }
}
