<?php

namespace App\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Information;
use App\Services\BaseService;

class CreateInformation extends BaseService implements ServiceInterface
{
    private Information $information;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'allows_multiple_entries' => 'nullable|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Create an information.
     *
     * @param  array  $data
     * @return Information
     */
    public function execute(array $data): Information
    {
        $this->validateRules($data);

        $this->information = Information::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'allows_multiple_entries' => $this->valueOrFalse($data, 'allows_multiple_entries'),
        ]);

        return $this->information;
    }
}
