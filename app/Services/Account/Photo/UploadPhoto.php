<?php

namespace App\Services\Account\Photo;

use App\Models\Account\Photo;
use App\Services\BaseService;

class UploadPhoto extends BaseService
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
            'photo' => 'required|file',
        ];
    }

    /**
     * Upload a photo.
     *
     * @param array $data
     * @return Photo
     */
    public function execute(array $data) : Photo
    {
        $this->validate($data);
        $array = $this->populateData($data);

        return Photo::create($array);
    }

    /**
     * Create an array with the necessary fields to create the photo object.
     *
     * @return array
     */
    private function populateData($data)
    {
        $photo = $data['photo'];
        $data = [
            'account_id' => $data['account_id'],
            'original_filename' => $photo->getClientOriginalName(),
            'filesize' => $photo->getClientSize(),
            'mime_type' => (new \Mimey\MimeTypes)->getMimeType($photo->guessClientExtension()),
        ];
        $filename = $photo->storePublicly('photos', config('filesystems.default'));

        return array_merge($data, [
            'new_filename' => $filename,
        ]);
    }
}
