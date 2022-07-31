<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Services\BaseService;
use App\Helpers\RequestHelper;
use App\Helpers\CountriesHelper;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\App;

class CreateUser extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
            'locale' => 'nullable',
            'ip_address' => 'nullable',
        ];
    }

    /**
     * Create a user.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        $this->validate($data);

        $ipAddress = $data['ip_address'] ?? RequestHelper::ip();

        $user = $this->createUser($data);
        $user = $this->setRegionalParameters($user, $ipAddress);
        $user->save();

        app(AcceptPolicy::class)->execute([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
        ]);

        return $user;
    }

    /**
     * Create a user.
     *
     * @param  array  $data
     * @return User
     */
    private function createUser($data): User
    {
        // create the user
        $user = new User();
        $user->account_id = $data['account_id'];
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->locale = $data['locale'] ?? App::getLocale();

        return $user;
    }

    /**
     * Set the regional default parameters.
     *
     * @param  User  $user
     * @param  string|null  $ipAddress
     * @return User
     */
    private function setRegionalParameters($user, $ipAddress): User
    {
        $infos = RequestHelper::infos($ipAddress);

        // Associate timezone and currency
        $currencyCode = $infos['currency'];
        $timezone = $infos['timezone'];
        if ($infos['country']) {
            $country = CountriesHelper::getCountry($infos['country']);
        } else {
            $country = CountriesHelper::getCountryFromLocale($user->locale);
        }

        // Timezone
        if (! is_null($timezone)) {
            $user->timezone = $timezone;
        } elseif (! is_null($country)) {
            $user->timezone = CountriesHelper::getDefaultTimezone($country);
        } else {
            $user->timezone = config('app.timezone');
        }

        // Currency
        if ((! is_null($currencyCode)
            && ! $this->associateCurrency($user, $currencyCode))
            || ! is_null($country)) {
            foreach ($country->getCurrencies() as $currency) {
                if ($this->associateCurrency($user, $currency['iso_4217_code'])) {
                    break;
                }
            }
        }

        // Temperature scale
        if (! is_null($country)) {
            switch ($country->getIsoAlpha2()) {
                case 'US':
                case 'BZ':
                case 'KY':
                    $user->temperature_scale = 'fahrenheit';
                    break;
                default:
                    $user->temperature_scale = 'celsius';
                    break;
            }
        } else {
            $user->temperature_scale = 'celsius';
        }

        return $user;
    }

    /**
     * Associate currency with the User.
     *
     * @param  User  $user
     * @param  string  $currency
     * @return bool
     */
    private function associateCurrency($user, $currency): bool
    {
        $currencyObj = Currency::where('iso', $currency)->first();
        if (! is_null($currencyObj)) {
            $user->currency()->associate($currencyObj);

            return true;
        }

        return false;
    }
}
