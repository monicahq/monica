<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;

class GetAdorableAvatarURL extends BaseService
{
    private const ADORABLE_API = 'https://api.adorable.io/avatars/';

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'uuid' => 'required|string',
            'size' => 'nullable|integer|between:1,2000',
        ];
    }

    /**
     * Get an url for an adorable avatar.
     * - http://avatars.adorable.io/ gives avatars based on a random string.
     *
     * @param array $data
     * @return string|null
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $size = $this->size($data);

        return self::ADORABLE_API.$size.'/'.$data['uuid'].'.png';
    }

    /**
     * Get the size for the avatar, based on a given parameter. Provides a
     * default otherwise.
     *
     * @param  array  $data
     * @return int
     */
    private function size(array $data)
    {
        if (isset($data['size'])) {
            return $data['size'];
        }

        return (int) config('monica.avatar_size');
    }
}
