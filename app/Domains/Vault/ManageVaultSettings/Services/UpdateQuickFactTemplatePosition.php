<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\VaultQuickFactsTemplate;
use App\Services\BaseService;

class UpdateQuickFactTemplatePosition extends BaseService implements ServiceInterface
{
    private VaultQuickFactsTemplate $quickFactTemplate;

    private int $pastPosition;

    private array $data;

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
            'new_position' => 'required|integer',
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
     * Update the quick fact template parameter's position.
     */
    public function execute(array $data): VaultQuickFactsTemplate
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->quickFactTemplate;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->quickFactTemplate = $this->vault->quickFactsTemplateEntries()
            ->findOrFail($this->data['vault_quick_facts_template_id']);

        $this->pastPosition = $this->quickFactTemplate->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->quickFactTemplate
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->vault->quickFactsTemplateEntries()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->vault->quickFactsTemplateEntries()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
