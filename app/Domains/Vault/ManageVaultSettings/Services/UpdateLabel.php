<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Label;
use App\Services\BaseService;
use Illuminate\Support\Str;

class UpdateLabel extends BaseService implements ServiceInterface
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
            'label_id' => 'required|integer|exists:labels,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'bg_color' => 'string|max:255',
            'text_color' => 'string|max:255',
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
     * Update a label.
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $label = $this->vault->labels()
            ->withCount('contacts')
            ->findOrFail($data['label_id']);

        $label->name = $data['name'];
        $label->bg_color = $data['bg_color'];
        $label->text_color = $data['text_color'];
        $label->description = $this->valueOrNull($data, 'description');
        $label->slug = Str::slug($data['name'], '-', language: currentLang());
        $label->save();

        return $label;
    }
}
