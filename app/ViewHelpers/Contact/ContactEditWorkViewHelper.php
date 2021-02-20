<?php

namespace App\ViewHelpers\Contact;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;

class ContactEditWorkViewHelper
{
    /**
     * Get all companies in the account.
     *
     * @param Account $account
     * @return Collection
     */
    public static function companies(Account $account): Collection
    {
        $companiesCollection = collect([]);
        $companies = $account->companies;
        foreach ($companies as $company) {
            $companiesCollection->push([
                'id' => $company->id,
                'name' => $company->name,
            ]);
        }

        return $companiesCollection;
    }
}
