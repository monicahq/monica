<?php

namespace App\Domains\Settings\ManageUserPreferences\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class StoreLocale extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'locale' => [
                'required',
                'string',
                'max:5',
                Rule::in(config('localizer.supported_locales')),
            ],
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Store name order preference for the given user.
     */
    public function execute(array $data): User
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->updateUser();

        return $this->author;
    }

    private function updateUser(): void
    {
        $this->author->locale = $this->data['locale'];
        $this->author->save();

        App::setLocale($this->data['locale']);
    }
}
