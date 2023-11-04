<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Label;
use App\Services\BaseService;
use Illuminate\Support\Str;

class CreateLabel extends BaseService implements ServiceInterface
{
    private Label $label;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'bg_color' => 'nullable|string|max:255',
            'text_color' => 'nullable|string|max:255',
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
     * Create a label.
     */
    public function execute(array $data): Label
    {
        $this->validateRules($data);

        $this->label = Label::create([
            'vault_id' => $data['vault_id'],
            'name' => $data['name'],
            'description' => $this->valueOrNull($data, 'description'),
            'slug' => Str::slug($data['name'], '-', language: currentLang()),
            'bg_color' => $this->valueOrNull($data, 'bg_color') ?? 'bg-neutral-200',
            'text_color' => $this->valueOrNull($data, 'text_color') ?? 'text-neutral-800',
        ]);

        return $this->label;
    }
}
