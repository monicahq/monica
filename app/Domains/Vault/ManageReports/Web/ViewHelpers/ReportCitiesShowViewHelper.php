<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\MapHelper;
use App\Helpers\WikipediaHelper;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Support\Str;

class ReportCitiesShowViewHelper
{
    public static function data(Vault $vault, string $city): array
    {
        $addresses = $vault->addresses()
            ->whereNotNull('city')
            ->where('city', 'like', $city)
            ->with('contacts')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'name' => Str::ucfirst($address->city),
                'address' => MapHelper::getAddressAsString($address),
                'contacts' => $address->contacts()
                    ->get()
                    ->map(fn (Contact $contact) => ContactCardHelper::data($contact)),
            ]);

        $wikipediaInformation = WikipediaHelper::getInformation($city);

        return [
            'city' => Str::ucfirst($city),
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
