<?php

namespace App\Domains\Contact\ManageContactAddresses\Web\Controllers;

use App\Helpers\MapHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

/**
 * Controller for serving static map images of contact addresses.
 * 
 * Proxies requests to Mapbox Static Maps API to avoid exposing API keys in frontend.
 * Returns 404 if Mapbox is not configured or address has no coordinates.
 */
class ContactModuleAddressImageController extends Controller
{
    /**
     * Display a static map image for an address.
     * 
     * Fetches static map image from MapHelper (Mapbox) and streams it to the client.
     * This proxies the request to avoid exposing API credentials.
     * 
     * @param Request $request The HTTP request
     * @param string $vaultId The vault ID
     * @param string $contactId The contact ID (unused but required for route)
     * @param int $addressId The address ID to display
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     * @return \Illuminate\Http\Response Stream of image data or 404
     */
    public function show(Request $request, string $vaultId, string $contactId, int $addressId, int $width, int $height)
    {
        $address = Address::where('vault_id', $vaultId)
            ->findOrFail($addressId);

        $url = MapHelper::getStaticImage($address, $width, $height);
        
        // Return 404 if map service not configured or no coordinates
        if ($url === null) {
            abort(404);
        }

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
