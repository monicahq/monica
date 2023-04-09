<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\MoodTrackingParameter;
use App\Services\BaseService;

class CreateMoodTrackingParameter extends BaseService implements ServiceInterface
{
    private MoodTrackingParameter $moodTrackingParameter;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'label' => 'required|string|max:255',
            'hex_color' => 'string|max:255',
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
     * Create a mood tracking parameter.
     */
    public function execute(array $data): MoodTrackingParameter
    {
        $this->validateRules($data);

        // determine the new position
        $newPosition = $this->vault->moodTrackingParameters()
            ->max('position');
        $newPosition++;

        $this->moodTrackingParameter = MoodTrackingParameter::create([
            'vault_id' => $data['vault_id'],
            'label' => $data['label'],
            'hex_color' => $data['hex_color'],
            'position' => $newPosition,
        ]);

        return $this->moodTrackingParameter;
    }
}
