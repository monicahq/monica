<?php

namespace App\Domains\Vault\ManageCompanies\Web\ViewHelpers;

use App\Models\Company;
use App\Models\Vault;

class CompanyViewHelper
{
    public static function data(Vault $vault): array
    {
        $collection = $vault->companies()->orderBy('name', 'asc')
            ->get()->map(function ($company) use ($vault) {
                return self::dto($vault, $company);
            });

        return [
            'companies' => $collection,
        ];
    }

    public static function dto(Vault $vault, Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'type' => $company->type,
        ];
    }
}
