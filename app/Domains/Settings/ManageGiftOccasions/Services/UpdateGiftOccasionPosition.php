<?php

namespace App\Domains\Settings\ManageGiftOccasions\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftOccasion;
use App\Services\BaseService;

class UpdateGiftOccasionPosition extends BaseService implements ServiceInterface
{
    private GiftOccasion $giftOccasion;

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
            'gift_occasion_id' => 'required|integer|exists:gift_occasions,id',
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
     * Update the gift occasion's position.
     */
    public function execute(array $data): GiftOccasion
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->giftOccasion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->giftOccasion = $this->account()->giftOccasions()
            ->findOrFail($this->data['gift_occasion_id']);

        $this->pastPosition = $this->giftOccasion->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        $this->giftOccasion
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        $this->account()->giftOccasions()
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        $this->account()->giftOccasions()
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
