<?php

namespace App\Services\Contact\Contact;

use App\Models\User\User;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class DeleteMeContact extends BaseService
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
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Set a contact as 'me' contact.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->validate($data);

        /** @var User */
        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        $user->me_contact_id = null;
        $user->save();

        return $user;
    }
}
