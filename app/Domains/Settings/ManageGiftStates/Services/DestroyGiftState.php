<?php

namespace App\Domains\Settings\ManageGiftStates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftState;
use App\Services\BaseService;

class DestroyGiftState extends BaseService implements ServiceInterface
{
    private GiftState $giftState;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'gift_state_id' => 'required|integer|exists:gift_states,id',
            'author_id' => 'required|uuid|exists:users,id',
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
     * Destroy a gift state.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->giftState = $this->account()->giftStates()
            ->findOrFail($data['gift_state_id']);

        $this->giftState->delete();

        $this->repositionEverything();
    }

    private function repositionEverything(): void
    {
        $this->account()->giftStates()
            ->where('position', '>', $this->giftState->position)
            ->decrement('position');
    }
}
