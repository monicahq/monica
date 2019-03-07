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
    public function execute(array $data)
    {
        $this->validate($data);

        try {
            if (! Gravatar::exists($data['email'])) {
                return;
            }
        } catch (\Creativeorange\Gravatar\Exceptions\InvalidEmailException $e) {
            // catch invalid email
            return;
        }

        $size = $this->size($data);

        return Gravatar::get($data['email'], [
                'size' => $size,
                'secure' => App::environment('production'),
            ]);
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

        return 200;
    }
}
