<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\MoodTrackingParameter;
use App\Services\BaseService;

class UpdateMoodTrackingParameterPosition extends BaseService implements ServiceInterface
{
    private MoodTrackingParameter $moodTrackingParameter;

    private int $pastPosition;

    private array $data;

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
            'new_position' => 'required|integer',
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
     * Update the mood tracking parameter's position.
     */
    public function execute(array $data): MoodTrackingParameter
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->moodTrackingParameter;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->moodTrackingParameter = $this->vault->moodTrackingParameters()
            ->findOrFail($this->data['mood_tracking_parameter_id']);

        $this->pastPosition = $this->moodTrackingParameter->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->moodTrackingParameter
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->vault->moodTrackingParameters()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->vault->moodTrackingParameters()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
