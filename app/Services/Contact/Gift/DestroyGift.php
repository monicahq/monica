<?php

namespace App\Services\Contact\Gift;

use App\Models\Contact\Gift;
use App\Services\BaseService;

class DestroyGift extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'gift_id' => 'required|integer|exists:gifts,id',
        ];
    }

    /**
     * Destroy a gift.
     *
     * @param  array  $data
     * @return bool
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $gift = Gift::where('account_id', $data['account_id'])
            ->findOrFail($data['gift_id']);

        $gift->contact->throwInactive();

        $gift->photos()->detach();

        $gift->delete();

        return true;
    }
}
