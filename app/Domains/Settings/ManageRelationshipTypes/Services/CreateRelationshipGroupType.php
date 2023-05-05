<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\RelationshipGroupType;
use App\Services\BaseService;

class CreateRelationshipGroupType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'name' => 'nullable|string|max:255',
            'name_translation_key' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'can_be_deleted' => 'required|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Create a relationship group type.
     */
    public function execute(array $data): RelationshipGroupType
    {
        $this->validateRules($data);

        $type = RelationshipGroupType::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'] ?? null,
            'name_translation_key' => $data['name_translation_key'] ?? null,
            'type' => $this->valueOrNull($data, 'type'),
            'can_be_deleted' => $data['can_be_deleted'],
        ]);

        return $type;
    }
}
