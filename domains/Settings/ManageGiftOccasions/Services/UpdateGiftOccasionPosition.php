<?php

namespace App\Settings\ManageGiftOccasions\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftOccasion;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateGiftOccasionPosition extends BaseService implements ServiceInterface
{
    private GiftOccasion $giftOccasion;
    private int $pastPosition;

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
            'gift_occasion_id' => 'required|integer|exists:gift_occasions,id',
            'new_position' => 'required|integer',
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
     * Update the gift occasion's position.
     *
     * @param  array  $data
     * @return GiftOccasion
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

        $this->giftOccasion = GiftOccasion::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['gift_occasion_id']);

        $this->pastPosition = DB::table('gift_occasions')
            ->where('id', $this->giftOccasion->id)
            ->select('position')
            ->first()->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('gift_occasions')
            ->where('id', $this->giftOccasion->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('gift_occasions')
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('gift_occasions')
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
