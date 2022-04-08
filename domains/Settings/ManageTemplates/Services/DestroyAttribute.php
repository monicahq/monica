<?php

namespace App\Settings\ManageTemplates\Services;

use App\Models\Attribute;
use App\Models\Information;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class DestroyAttribute extends BaseService implements ServiceInterface
{
    private Attribute $attribute;

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
            'attribute_id' => 'required|integer|exists:attributes,id',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Destroy an attribute.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->attribute = Attribute::findOrFail($data['attribute_id']);

        Information::where('account_id', $data['account_id'])
            ->findOrFail($this->attribute->information_id);

        $this->attribute->delete();
    }
}
