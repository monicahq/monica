<?php

namespace App\Services\Account\Photo;

use App\Models\Account\Photo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class DestroyPhoto extends BaseService
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
            'photo_id' => 'required|integer',
        ];
    }

    /**
     * Destroy a photo.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);
        $photo = Photo::where('account_id', $data['account_id'])
            ->findOrFail($data['photo_id']);
        // Delete the physical photo
        // Throws FileNotFoundException
        Storage::delete($photo->new_filename);
        // Delete the object in the DB
        $photo->delete();

        return true;
    }
}
