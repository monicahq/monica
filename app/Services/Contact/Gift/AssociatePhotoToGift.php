<?php

namespace App\Services\Contact\Gift;

use App\Models\Contact\Gift;
use App\Models\Account\Photo;
use App\Services\BaseService;

class AssociatePhotoToGift extends BaseService
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
            'photo_id' => 'required|integer|exists:photos,id',
            'gift_id' => 'required|integer|exists:gifts,id',
        ];
    }

    /**
     * Link a photo to a gift.
     *
     * @param  array  $data
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $photo = Photo::where('account_id', $data['account_id'])
            ->findOrFail($data['photo_id']);

        $gift = Gift::where('account_id', $data['account_id'])
            ->findOrFail($data['gift_id']);

        $gift->contact->throwInactive();

        $gift->photos()->syncWithoutDetaching([$photo->id]);

        return $gift;
    }
}
