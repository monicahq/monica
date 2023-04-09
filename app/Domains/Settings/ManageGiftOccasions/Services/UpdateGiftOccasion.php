<?php

namespace App\Domains\Settings\ManageGiftOccasions\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftOccasion;
use App\Services\BaseService;

class UpdateGiftOccasion extends BaseService implements ServiceInterface
{
    private array $data;

    private GiftOccasion $giftOccasion;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'gift_occasion_id' => 'required|integer|exists:gift_occasions,id',
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
     * Update a gift occasion.
     */
    public function execute(array $data): GiftOccasion
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->giftOccasion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->giftOccasion = $this->account()->giftOccasions()
            ->findOrFail($this->data['gift_occasion_id']);
    }

    private function update(): void
    {
        $this->giftOccasion->label = $this->data['label'];
        $this->giftOccasion->save();
    }
}
