<?php

namespace App\Domains\Settings\ManageGiftStates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftState;
use App\Services\BaseService;

class UpdateGiftStatePosition extends BaseService implements ServiceInterface
{
    private GiftState $giftState;

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
            'gift_state_id' => 'required|integer|exists:gift_states,id',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update the gift state's position.
     */
    public function execute(array $data): GiftState
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->giftState;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->giftState = $this->account()->giftStates()
            ->findOrFail($this->data['gift_state_id']);

        $this->pastPosition = $this->giftState->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->giftState
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->account()->giftStates()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->account()->giftStates()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
