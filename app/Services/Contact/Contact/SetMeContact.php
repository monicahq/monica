<?php

namespace App\Services\Contact\Contact;

use App\Models\User\User;
use App\Services\BaseService;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;

class SetMeContact extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
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

        if (AccountHelper::hasLimitations($user->account)) {
            abort(402);
        }

        /** @var Contact */
        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $user->me_contact_id = $contact->id;
        $user->save();

        return $user;
    }
}
