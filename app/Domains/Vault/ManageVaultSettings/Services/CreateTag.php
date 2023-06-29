<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Tag;
use App\Services\BaseService;
use Illuminate\Support\Str;

class CreateTag extends BaseService implements ServiceInterface
{
    private Tag $tag;

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
     * Create a tag.
     */
    public function execute(array $data): Tag
    {
        $this->validateRules($data);

        $this->tag = Tag::create([
            'vault_id' => $data['vault_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name'], '-', language: currentLang()),
        ]);

        return $this->tag;
    }
}
