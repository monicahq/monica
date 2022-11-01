<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Module;
use App\Services\BaseService;

class UpdateModule extends BaseService implements ServiceInterface
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
            'module_id' => 'required|integer|exists:modules,id',
            'name' => 'required|string|max:255',
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
     * Update a module.
     *
     * @param  array  $data
     * @return Module
     */
    public function execute(array $data): Module
    {
        $this->validateRules($data);

        $this->module = Module::where('account_id', $data['account_id'])
            ->findOrFail($data['module_id']);

        $this->module->name = $data['name'];
        $this->module->save();

        return $this->module;
    }
}
