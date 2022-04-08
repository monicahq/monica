<?php

namespace App\Settings\ManageTemplates\Services;

use App\Models\Module;
use App\Models\Template;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class DestroyModule extends BaseService implements ServiceInterface
{
    private Template $template;

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
     * Destroy a module.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->module = Module::where('account_id', $data['account_id'])
            ->findOrFail($data['module_id']);

        if (! $this->module->can_be_deleted) {
            throw new \Exception('The module cannot be deleted.');
        }

        $this->module->delete();
    }
}
