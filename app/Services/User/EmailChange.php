<?php

namespace App\Services\User;

use App\Services\BaseService;
use App\Notifications\ConfirmEmail;
use App\Exceptions\MissingParameterException;

class EmailChange extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'email',
    ];

    /**
     * Update email of the user.
     *
     * @param array $data
     */
    public function execute(array $data)
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $user = auth()->user();

        // Change email of the user
        $user->email = $data['email'];

        if (config('monica.signup_double_optin')) {
            // Resend validation token
            $user->confirmation_code = str_random(30);
            $user->confirmed = false;
            $user->save();

            $user->notify(new ConfirmEmail);

            return;
        }

        $user->save();
    }
}
