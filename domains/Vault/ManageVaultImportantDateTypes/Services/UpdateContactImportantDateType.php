<?php

namespace App\Vault\ManageVaultImportantDateTypes\Services;

use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDateType;

class UpdateContactImportantDateType extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
            'internal_type' => 'nullable|string|max:255',
            'can_be_deleted' => 'nullable|boolean',
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
     * Update a contact important date type.
     *
     * @param  array  $data
     * @return ContactImportantDateType
     */
    public function execute(array $data): ContactImportantDateType
    {
        $this->validateRules($data);

        $type = ContactImportantDateType::where('vault_id', $data['vault_id'])
            ->findOrFail($data['contact_important_date_type_id']);

        $type->label = $data['label'];
        $type->can_be_deleted = $this->valueOrTrue($data, 'can_be_deleted');
        $type->internal_type = $this->valueOrNull($data, 'internal_type');
        $type->save();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_important_date_type_updated',
            'objects' => json_encode([
                'type_label' => $type->label,
            ]),
        ])->onQueue('low');

        return $type;
    }
}
