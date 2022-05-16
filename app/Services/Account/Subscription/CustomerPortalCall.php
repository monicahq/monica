<?php

namespace App\Services\Account\Subscription;

use App\Exceptions\NoCustomerPortalSecretsException;
use App\Services\BaseService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Exceptions\NoCustomerPortalSetException;
use Illuminate\Support\Facades\Cache;

class CustomerPortalCall extends BaseService
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'licence_key' => 'required|string:4096',
        ];
    }

    /**
     * Check if the licence key given by the user is a valid licence key.
     * If it is, activate the licence key and set the valid_until_at date.
     *
     * @param  array  $data
     * @return int
     */
    public function execute(array $data): int
    {
        $this->validate($data);
        $this->data = $data;

        $this->validateEnvVariables();

        $accessToken = $this->getAccessToken();
        $response = $this->makeRequestToCustomerPortal($accessToken);

        return $response->status();
    }

    private function validateEnvVariables(): void
    {
        if (config('monica.customer_portal_url') === '') {
            throw new NoCustomerPortalSetException;
        }

        if (config('monica.customer_portal_client_id') === null || config('monica.customer_portal_client_secret') === null) {
            throw new NoCustomerPortalSecretsException;
        }
    }

    private function getAccessToken(): string
    {
        return Cache::remember('customer_portal_access_token', 604800, function () {
            $url = config('monica.customer_portal_url').'/oauth/token';

            $response = Http::asForm()->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => config('monica.customer_portal_client_id'),
                'client_secret' => config('monica.customer_portal_client_secret'),
                'scope' => 'manage-key',
            ]);
            $response->throw();

            $json = $response->json();
            if (! isset($json['access_token'])) {
                throw new \Exception("No access_token");
            }

            return $json['access_token'];
        });
    }

    private function makeRequestToCustomerPortal(string $accessToken): Response
    {
        $url = config('monica.customer_portal_url').'/api/validate';

        return Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, [
                'licence_key' => $this->data['licence_key']
            ]);
    }
}
