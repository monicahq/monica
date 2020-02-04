<?php

namespace App\Services\Account\Settings;

use App\Services\BaseService;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ArchiveAllContacts extends BaseService
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
        ];
    }

    /**
     * Archive all the contacts in the account.
     *
     * This method is used by a user who wants to downgrade his plan.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        try {
            DB::table('contacts')
                ->where('account_id', $data['account_id'])
                ->update(['is_active' => 0]);
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }
}
