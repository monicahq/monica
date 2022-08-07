<?php

namespace App\Settings\ManageGiftStates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GiftState;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateGiftStatePosition extends BaseService implements ServiceInterface
{
    private GiftState $giftState;

    private int $pastPosition;

    private array $data;

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
            'gift_state_id' => 'required|integer|exists:gift_states,id',
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
     * Update the gift state's position.
     *
     * @param  array  $data
     * @return GiftState
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

        $this->giftState = GiftState::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['gift_state_id']);

        $this->pastPosition = DB::table('gift_states')
            ->where('id', $this->giftState->id)
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

        DB::table('gift_states')
            ->where('id', $this->giftState->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('gift_states')
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('gift_states')
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
