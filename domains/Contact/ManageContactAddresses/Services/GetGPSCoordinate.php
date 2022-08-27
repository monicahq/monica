<?php

namespace App\Contact\ManageContactAddresses\Services;

use App\Exceptions\EnvVariablesNotSetException;
use App\Helpers\MapHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\Company\Place;
use App\Services\BaseService;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GetGPSCoordinate extends BaseService implements ServiceInterface
{
    private Address $address;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'address_id' => 'required|integer|exists:addresses,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Get the latitude and longitude from a place.
     * This method uses LocationIQ to process the geocoding.
     * It should always be done through a job, and not be called directly.
     * Typically, the job FetchAddressGeocoding calls this service.
     *
     * @param  array  $data
     * @return Address
     */
    public function execute(array $data): Address
    {
        $this->data = $data;
        $this->validate();

        $this->getCoordinates();

        return $this->address;
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
            $response = Http::get($query);
            $response->throw();

            $this->address->latitude = $response->json('0.lat');
            $this->address->longitude = $response->json('0.lon');
            $this->address->save();
        } catch (HttpClientException $e) {
            Log::error('Error calling location_iq: '.$e);
            throw new HttpClientException();
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
