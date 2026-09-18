<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;

/**
 * Put one setting back to what the deployment supplies, by dropping what was
 * stored for it.
 *
 * Reverting a setting nobody changed is not an error. The outcome asked for is
 * already true, and refusing it would make the screen and the API disagree about
 * whether an absent row is a state or a mistake. For a secret this is also how
 * one is cleared, since what is stored cannot be read back to be edited.
 */
class RevertInstanceSetting
{
    public function __construct(
        private readonly User $administrator,
        private readonly InstanceSettingEnum $setting,
    ) {}

    public function execute(): void
    {
        $row = InstanceSetting::query()->firstWhere('name', $this->setting->value);

        if ($row === null) {
            return;
        }

        $row->delete();

        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::InstanceSettingReverted,
            parameters: ['name' => __($this->setting->description())],
        )->onQueue('low');
    }
}
