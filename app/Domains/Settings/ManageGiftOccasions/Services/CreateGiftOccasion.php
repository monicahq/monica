<?php

namespace App\Domains\Settings\ManageGiftOccasions\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftOccasion;
use App\Services\BaseService;

class CreateGiftOccasion extends BaseService implements ServiceInterface
{
    private array $data;

    private GiftOccasion $giftOccasion;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'label' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Create a gift occasion.
     *
     * @param  array  $data
     * @return GiftOccasion
     */
    public function execute(array $data): GiftOccasion
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->giftOccasion;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position of the template page
        $newPosition = $this->account()->giftOccasions()
            ->max('position');
        $newPosition++;

        $this->giftOccasion = GiftOccasion::create([
            'account_id' => $this->data['account_id'],
            'label' => $this->data['label'],
            'position' => $newPosition,
        ]);
    }
}
