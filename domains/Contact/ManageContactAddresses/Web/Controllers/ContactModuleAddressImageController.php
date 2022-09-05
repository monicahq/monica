<?php

namespace App\Contact\ManageContactAddresses\Web\Controllers;

use App\Helpers\MapHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

class ContactModuleAddressImageController extends Controller
{
    public function show(Request $request, int $vaultId, int $contactId, int $addressId, int $width, int $height)
    {
        $address = Address::where('contact_id', $contactId)
            ->findOrFail($addressId);

        $url = MapHelper::getStaticImage($address, $width, $height);

        $response = Http::get($url)
            ->throw();

        return Response::stream(function () use ($response) {
            echo $response->body();
        },
            200,
            Arr::only($response->headers(), [
                'Content-Length',
                'Content-Type',
                'Cache-Control',
                'Date',
                'ETag',
            ])
        );
    }
}
