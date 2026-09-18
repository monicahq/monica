<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Drop a stored value for a setting the product no longer carries.
 *
 * It refuses a name the catalogue still carries, so this cannot become a second
 * way of deleting a live setting that skips {@see RevertInstanceSetting} and the
 * record entry that goes with it.
 */
class RemoveOrphanedInstanceSetting
{
    public function __construct(
        private readonly User $administrator,
        private readonly string $name,
    ) {}

    public function execute(): void
    {
        if (InstanceSettingEnum::tryFrom($this->name) !== null) {
            throw new ModelNotFoundException("{$this->name} is a setting the product still carries.");
        }

        $row = InstanceSetting::query()->firstWhere('name', $this->name);

        if ($row === null) {
            throw new ModelNotFoundException("Nothing is stored under {$this->name}.");
        }

        $row->delete();

        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::InstanceSettingReverted,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}
