<?php

namespace App\Settings\ManageCurrencies\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ToggleCurrency extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'currency_id' => 'required|integer|exists:currencies,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Toggle the currency in the account.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $record = DB::table('account_currencies')
            ->where('account_id', $data['account_id'])
            ->where('currency_id', $data['currency_id'])
            ->first();

        DB::table('account_currencies')
            ->where('account_id', $data['account_id'])
            ->where('currency_id', $data['currency_id'])
            ->update([
                'active' => ! $record->active,
            ]);
    }
}
