<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Models\Settings\Term;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptPolicy extends BaseService
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
            'ip_address' => 'nullable|string|max:255',
        ];
    }

    /**
     * Accept the latest user policy.
     *
     * @param  array  $data
     * @return Term
     *
     * @throws \Exception
     */
    public function execute(array $data): Term
    {
        $this->validate($data);

        try {
            $user = User::where('account_id', $data['account_id'])
                ->findOrFail($data['user_id']);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException(trans('app.error_user_account'));
        }

        $latestTerm = Term::latest()->first();

        if (! $latestTerm) {
            throw new \Exception(trans('app.error_no_term'));
        }

        $user->terms()->syncWithoutDetaching([$latestTerm->id => [
            'account_id' => $user->account_id,
            'ip_address' => $this->nullOrValue($data, 'ip_address'),
        ]]);

        return $latestTerm;
    }
}
