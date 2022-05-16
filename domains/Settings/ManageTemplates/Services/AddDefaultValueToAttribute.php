<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Attribute;
use App\Models\AttributeDefaultValue;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddDefaultValueToAttribute extends BaseService implements ServiceInterface
{
    private array $data;
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
            'value' => 'required|string|max:255',
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
     * Add a default value to an attribute.
     *
     * @param  array  $data
     * @return Attribute
     */
    public function execute(array $data): Attribute
    {
        $this->data = $data;
        $this->validate();

        AttributeDefaultValue::create([
            'attribute_id' => $data['attribute_id'],
            'value' => $data['value'],
        ]);

        return $this->attribute;
    }

    public function validate(): void
    {
        $this->validateRules($this->data);

        $this->attribute = Attribute::findOrFail($this->data['attribute_id']);

        if ($this->attribute->information->account_id != $this->data['account_id']) {
            throw new ModelNotFoundException();
        }
    }
}
