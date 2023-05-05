<?php

namespace App\Domains\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Models\CallReasonType;
use App\Services\BaseService;

class CreateCallReasonType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'label' => 'nullable|string|max:255',
            'label_translation_key' => 'nullable|string|max:255',
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
     * Create a call reason type.
     */
    public function execute(array $data): CallReasonType
    {
        $this->validateRules($data);

        $type = CallReasonType::create([
            'account_id' => $data['account_id'],
            'label' => $data['label'] ?? null,
            'label_translation_key' => $data['label_translation_key'] ?? null,
        ]);

        return $type;
    }
}
