<?php

namespace App\Domains\Contact\ManageJobInformation\Web\ViewHelpers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Collection;

class ModuleCompanyViewHelper
{
    public static function data(Contact $contact): array
    {
        $company = $contact->company;

        return [
            'job_position' => $contact->job_position,
            'company' => $company ? self::dto($company, $contact) : null,
            'url' => [
                'index' => route('contact.companies.list.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'update' => route('contact.job_information.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.job_information.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function list(Vault $vault, Contact $contact): Collection
    {
        return $vault->companies()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Company $company) => self::dto($company, $contact));
    }

    public static function dto(Company $company, Contact $contact): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'type' => $company->type,
            'selected' => $company->id === $contact->company_id,
        ];
    }
}
