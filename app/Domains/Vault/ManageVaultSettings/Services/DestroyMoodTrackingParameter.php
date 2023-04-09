<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyMoodTrackingParameter extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'mood_tracking_parameter_id' => 'required|integer|exists:mood_tracking_parameters,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Destroy a mood tracking parameter.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $moodTrackingParameter = $this->vault->moodTrackingParameters()
            ->findOrFail($data['mood_tracking_parameter_id']);

        $moodTrackingParameter->delete();
    }
}
