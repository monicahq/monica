<?php

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;

class GetGravatar extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|string',
        ];
    }

    /**
     * Get Gravatar, if it exists.
     *
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validate($data);

        try {
            if (! app('gravatar')->exists($email->data)) {
                continue;
            }
        } catch (\Creativeorange\Gravatar\Exceptions\InvalidEmailException $e) {
            // catch invalid email
            continue;
        }

        return app('gravatar')->get($email->data, [
                'size' => $size,
                'secure' => config('app.env') === 'production',
            ]);
    }
}
