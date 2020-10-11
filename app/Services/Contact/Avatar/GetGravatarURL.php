<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;
use Illuminate\Support\Facades\App;
use Creativeorange\Gravatar\Facades\Gravatar;

class GetGravatarURL extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'size' => 'nullable|integer|between:1,2000',
        ];
    }

    /**
     * Get Gravatar, if it exists.
     *
     * @param array $data
     * @return string|null
     */
    public function execute(array $data): ?string
    {
        $this->validate($data);

        if ($this->exists($data)) {
            $size = $this->size($data);

            return Gravatar::get($data['email'], [
                'size' => $size,
                'secure' => App::environment('production'),
            ]);
        }

        return null;
    }

    /**
     * Test given email.
     *
     * @param array $data
     * @return bool
     */
    private function exists(array $data)
    {
        try {
            return Gravatar::exists($data['email']);
        } catch (\Creativeorange\Gravatar\Exceptions\InvalidEmailException $e) {
            // catch invalid email
            return false;
        }
    }

    /**
     * Get the size for the gravatar, based on a given parameter. Provides a
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
