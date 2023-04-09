<?php

namespace App\Domains\Settings\ManageGiftStates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftState;
use App\Services\BaseService;

class UpdateGiftState extends BaseService implements ServiceInterface
{
    private array $data;

    private GiftState $giftState;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'gift_state_id' => 'required|integer|exists:gift_states,id',
            'label' => 'required|string|max:255',
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
     * Update a gift state.
     */
    public function execute(array $data): GiftState
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->giftState;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->giftState = $this->account()->giftStates()
            ->findOrFail($this->data['gift_state_id']);
    }

    private function update(): void
    {
        $this->giftState->label = $this->data['label'];
        $this->giftState->save();
    }
}
