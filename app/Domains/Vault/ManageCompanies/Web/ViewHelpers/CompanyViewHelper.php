<?php

namespace App\Domains\Vault\ManageCompanies\Web\ViewHelpers;

use App\Models\Company;
use App\Models\Vault;

class CompanyViewHelper
{
    public static function data(Vault $vault): array
    {
        $companies = $vault->companies()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Company $company) => self::dto($vault, $company));

        return [
            'companies' => $companies,
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
