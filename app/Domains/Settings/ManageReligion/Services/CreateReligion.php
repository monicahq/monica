<?php

namespace App\Domains\Settings\ManageReligion\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Religion;
use App\Services\BaseService;

class CreateReligion extends BaseService implements ServiceInterface
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
     * Create a religion.
     */
    public function execute(array $data): Religion
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->religion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position of the religion
        $newPosition = $this->account()->religions()
            ->max('position');
        $newPosition++;

        $this->religion = Religion::create([
            'account_id' => $this->data['account_id'],
            'name' => $this->data['name'],
            'position' => $newPosition,
        ]);
    }
}
