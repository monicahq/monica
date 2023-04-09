<?php

namespace App\Domains\Settings\ManageGiftStates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftState;
use App\Services\BaseService;

class CreateGiftState extends BaseService implements ServiceInterface
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
     * Create a gift state.
     */
    public function execute(array $data): GiftState
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->giftState;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position
        $newPosition = $this->account()->giftStates()
            ->max('position');
        $newPosition++;

        $this->giftState = GiftState::create([
            'account_id' => $this->data['account_id'],
            'label' => $this->data['label'],
            'position' => $newPosition,
        ]);
    }
}
