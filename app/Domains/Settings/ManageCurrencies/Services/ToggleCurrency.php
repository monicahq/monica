<?php

namespace App\Domains\Settings\ManageCurrencies\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ToggleCurrency extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'currency_id' => 'required|integer|exists:currencies,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
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
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        DB::table('account_currency')
            ->where('account_id', $data['account_id'])
            ->where('currency_id', $data['currency_id'])
            ->update([
                'active' => DB::raw('NOT active'),
            ]);
    }
}
