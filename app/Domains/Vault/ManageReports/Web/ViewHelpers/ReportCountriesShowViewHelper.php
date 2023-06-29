<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\MapHelper;
use App\Helpers\WikipediaHelper;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Str;

class ReportCountriesShowViewHelper
{
    public static function data(Vault $vault, string $country): array
    {
        $addresses = $vault->addresses()
            ->whereNotNull('country')
            ->where('country', 'like', $country)
            ->with('contacts')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'name' => Str::ucfirst($address->country),
                'address' => MapHelper::getAddressAsString($address),
                'contacts' => $address->contacts()
                    ->get()
                    ->map(fn (Contact $contact) => ContactCardHelper::data($contact)),
            ]);

        $wikipediaInformation = WikipediaHelper::getInformation($country);

        return [
            'country' => Str::ucfirst($country),
            'addresses' => $addresses,
            'wikipedia' => [
                'url' => $wikipediaInformation['url'],
                'description' => $wikipediaInformation['description'],
                'thumbnail' => $wikipediaInformation['thumbnail'],
            ],
            'url' => [
                'addresses' => route('vault.reports.addresses.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}
