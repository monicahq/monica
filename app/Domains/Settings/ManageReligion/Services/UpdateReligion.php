<?php

namespace App\Domains\Settings\ManageReligion\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Religion;
use App\Services\BaseService;

class UpdateReligion extends BaseService implements ServiceInterface
{
    private array $data;

    private Religion $religion;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'religion_id' => 'required|integer|exists:religions,id',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a religion.
     */
    public function execute(array $data): Religion
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->religion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->religion = $this->account()->religions()
            ->findOrFail($this->data['religion_id']);
    }

    private function update(): void
    {
        $this->religion->name = $this->data['name'];
        $this->religion->save();
    }
}
