<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\VaultQuickFactsTemplate;
use App\Services\BaseService;

class UpdateQuickFactTemplate extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'vault_quick_facts_template_id' => 'required|integer|exists:vault_quick_facts_templates,id',
            'label' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
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
     * Update a quick fact template entry.
     */
    public function execute(array $data): VaultQuickFactsTemplate
    {
        $this->validateRules($data);

        $quickFactTemplateEntry = $this->vault->quickFactsTemplateEntries()
            ->findOrFail($data['vault_quick_facts_template_id']);

        $quickFactTemplateEntry->label = $data['label'];
        $quickFactTemplateEntry->save();

        return $quickFactTemplateEntry;
    }
}
