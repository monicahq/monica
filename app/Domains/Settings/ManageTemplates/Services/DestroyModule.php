<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyModule extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'module_id' => 'required|integer|exists:modules,id',
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
     * Destroy a module.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $module = $this->account()->modules()
            ->findOrFail($data['module_id']);

        if (! $module->can_be_deleted) {
            throw new CantBeDeletedException('The module cannot be deleted.');
        }

        $module->delete();
    }
}
