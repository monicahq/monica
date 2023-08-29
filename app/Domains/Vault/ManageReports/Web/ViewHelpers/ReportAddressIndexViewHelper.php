<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Models\Address;
use App\Models\Vault;
use Illuminate\Support\Str;

use function Safe\mb_convert_encoding;

class ReportAddressIndexViewHelper
{
    public static function data(Vault $vault): array
    {
        // all the cities in the vault
        // the distinct method does not do a case-insensitive search, so we need
        // to do it manually. there would be a way to do it with raw SQL but
        // it wouldn't work on sqlite
        $cities = $vault->addresses()
            ->select('id', 'city')
            ->whereNotNull('city')
            ->withCount('contacts')
            ->distinct('city')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'name' => Str::ucfirst($address->city),
                'contacts' => $address->contacts_count,
                'url' => [
                    'index' => route('vault.reports.addresses.cities.show', [
                        'vault' => $vault->id,
                        'city' => urlencode(mb_convert_encoding($address->city, 'UTF-8')),
                    ]),
                ],
            ])
            ->unique('name');

        // all the countries in the vault
        $countries = $vault->addresses()
            ->select('id', 'country')
            ->whereNotNull('country')
            ->withCount('contacts')
            ->distinct('country')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'name' => Str::ucfirst($address->country),
                'contacts' => $address->contacts_count,
                'url' => [
                    'index' => route('vault.reports.addresses.countries.show', [
                        'vault' => $vault->id,
                        'country' => urlencode(mb_convert_encoding($address->country, 'UTF-8')),
                    ]),
                ],
            ])
            ->unique('name');

        return [
            'cities' => $cities,
            'countries' => $countries,
        ];
    }
}
