<?php

namespace App\Domains\BlackBookd\ManageSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Account;
use App\Services\BaseService;

class UpdateBlackBookdAccountSettings extends BaseService implements ServiceInterface
{
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'blackbookd_enabled' => 'required|boolean',
            'blackbookd_blur_dashboard' => 'required|boolean',
            'blackbookd_partner_lookback_days' => 'required|integer|between:1,730',
        ];
    }

    public function permissions(): array
    {
        return ['author_must_belong_to_account', 'author_must_be_account_administrator'];
    }

    public function execute(array $data): Account
    {
        $this->validateRules($data);
        $account = Account::findOrFail($data['account_id']);
        $account->update([
            'blackbookd_enabled' => $data['blackbookd_enabled'],
            'blackbookd_blur_dashboard' => $data['blackbookd_blur_dashboard'],
            'blackbookd_partner_lookback_days' => $data['blackbookd_partner_lookback_days'],
        ]);

        return $account;
    }
}