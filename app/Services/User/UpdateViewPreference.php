<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Services\BaseService;
use App\Helpers\RequestHelper;
use App\Helpers\CountriesHelper;
use App\Models\Settings\Currency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

class UpdateViewPreference extends BaseService
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
            'preference' => 'required|string|max:255',
        ];
    }

    /**
     * Set the contact view preference.
     *
     * @param array $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->validate($data);

        try {
            $user = User::where('account_id', $data['account_id'])
                ->findOrFail($data['user_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException(trans('app.error_user_account'));
        }

        $user->contacts_sort_order = $data['preference'];
        $user->save();

        return $user;
    }
}
