<?php

namespace App\Domains\Settings\CreateAccount\Services;

use App\Actions\Fortify\PasswordValidationRules;
use App\Domains\Settings\CreateAccount\Jobs\SetupAccount;
use App\Helpers\CountriesHelper;
use App\Helpers\RequestHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Account;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class CreateAccount extends BaseService implements ServiceInterface
{
    use PasswordValidationRules;

    private Account $account;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|unique:users,email|email|max:255',
            'password' => $this->passwordRules(false),
        ];
    }

    /**
     * Create an account.
     */
    public function execute(array $data): User
    {
        $this->data = $data;
        $this->validateRules($this->data);

        $this->account = Account::create([
            'storage_limit_in_mb' => config('monica.default_storage_limit_in_mb'),
        ]);

        return tap($this->addFirstUser(), function ($user) {
            $this->setupAccount($user);
        });
    }

    private function addFirstUser(): User
    {
        $data = [
            'account_id' => $this->account->id,
            'first_name' => $this->data['first_name'],
            'last_name' => $this->data['last_name'],
            'email' => $this->data['email'],
            'password' => isset($this->data['password']) ? Hash::make($this->data['password']) : null,
            'locale' => App::getLocale(),
            'is_account_administrator' => true,
        ];

        $this->setRegionalParameters($data);

        return User::create($data);
    }

    private function setupAccount(User $user): void
    {
        $request = [
            'account_id' => $this->account->id,
            'author_id' => $user->id,
        ];

        SetupAccount::dispatch($request)->onQueue('high');
    }

    /**
     * Set the regional default parameters.
     */
    private function setRegionalParameters(array &$data): void
    {
        $infos = RequestHelper::infos(Request::ip());

        if ($infos['country']) {
            $country = CountriesHelper::getCountry($infos['country']);
        } else {
            $country = CountriesHelper::getCountryFromLocale($data['locale']);
        }

        // Associate timezone
        if ($infos['timezone'] !== null) {
            $data['timezone'] = $infos['timezone'];
        } elseif ($country !== null) {
            $data['timezone'] = CountriesHelper::getDefaultTimezone($country);
        } else {
            $data['timezone'] = config('app.timezone');
        }

        // Associate distance format
        $data['distance_format'] = $country !== null
            ? CountriesHelper::getDefaultDistanceFormat($country)
            : User::DISTANCE_UNIT_KM;

        // Associate date format
        $data['date_format'] = $country !== null
            ? CountriesHelper::getDefaultDateFormat($country)
            : ($data['locale'] === 'en' ? 'MMM DD, YYYY' : 'DD MMM YYYY');
    }
}
