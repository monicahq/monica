<?php

namespace App\Settings\ManageHelp\Services;

use App\Models\User;
use App\Services\BaseService;

class UpdateHelpPreferences extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'visibility' => 'required|boolean',
        ];
    }

    /**
     * Saves the help preferences.
     * If it's set to true, it will show an help button next to the main features
     * on the screen.
     *
     * @param array $data
     *
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validateRules($data);

        $user = User::findOrFail($data['user_id']);

        $user->help_shown = $data['visibility'];
        $user->save();

        return true;
    }
}
