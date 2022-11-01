<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Services\BaseService;

class CreateModule extends BaseService implements ServiceInterface
{
    private Module $module;

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
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'can_be_deleted' => 'required|boolean',
            'reserved_to_contact_information' => 'nullable|boolean',
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
     * Create a module.
     *
     * @param  array  $data
     * @return Module
     */
    public function execute(array $data): Module
    {
        $this->validateRules($data);

        $this->module = Module::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'type' => $this->valueOrNull($data, 'type'),
            'can_be_deleted' => $data['can_be_deleted'],
            'reserved_to_contact_information' => $this->valueOrFalse($data, 'reserved_to_contact_information'),
        ]);

        return $this->module;
    }
}
