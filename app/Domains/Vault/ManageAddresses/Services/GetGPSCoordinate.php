<?php

namespace App\Domains\Vault\ManageAddresses\Services;

use App\Exceptions\EnvVariablesNotSetException;
use App\Helpers\MapHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Services\QueuableService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GetGPSCoordinate extends QueuableService implements ServiceInterface
{
    private Address $address;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'address_id' => 'required|integer|exists:addresses,id',
        ];
    }

    /**
     * Get the latitude and longitude from a place.
     * This method uses LocationIQ to process the geocoding.
     * It should always be done through a job, and not be called directly.
     */
    public function execute(array $data): void
    {
        $this->validate();

        $this->getCoordinates();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->address = Address::findOrFail($this->data['address_id']);
    }

    private function getCoordinates()
    {
        $query = $this->buildQuery();

        try {
            $response = Http::get($query)->throw();

            $this->address->latitude = $response->json('0.lat');
            $this->address->longitude = $response->json('0.lon');
            $this->address->save();
        } catch (RequestException $e) {
            Log::error('Error calling location_iq: '.$e->response->json()['error']);
            $this->fail($e);
        }
    }

    private function buildQuery(): string
    {
        if (is_null(config('monica.location_iq_api_key'))) {
            throw new EnvVariablesNotSetException('Env variables are not set for Location IQ');
        }

        $query = http_build_query([
            'format' => 'json',
            'key' => config('monica.location_iq_api_key'),
            'q' => MapHelper::getAddressAsString($this->address),
        ]);

        return Str::finish(config('monica.location_iq_url'), '/').'search.php?'.$query;
    }
}
