<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Tag;
use App\Services\BaseService;
use Illuminate\Support\Str;

class UpdateTag extends BaseService implements ServiceInterface
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
            'tag_id' => 'required|integer|exists:tags,id',
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
     * Update a tag.
     */
    public function execute(array $data): Tag
    {
        $this->validateRules($data);

        $tag = $this->vault->tags()
            ->findOrFail($data['tag_id']);

        $tag->name = $data['name'];
        $tag->slug = Str::slug($data['name'], '-', language: currentLang());
        $tag->save();

        return $tag;
    }
}
