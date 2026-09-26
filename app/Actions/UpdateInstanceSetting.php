<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Enums\InstanceSettingKindEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Change one setting of this instance, so it governs from the next request.
 *
 * The refusals are thrown as validation failures because both surfaces already
 * answer one correctly: the screen redirects back with the reason beside the
 * control, and the API answers 422 naming the setting. A setting the deployment
 * fixes is refused the same way, since what the caller needs is the reason, not
 * a bespoke exception class.
 */
class UpdateInstanceSetting
{
    private InstanceSetting $row;

    private ?string $before = null;

    public function __construct(
        private readonly ?User $administrator,
        private readonly InstanceSettingEnum $setting,
        private readonly ?string $value,
    ) {}

    public function execute(): InstanceSetting
    {
        $this->validate();

        $this->row = InstanceSetting::query()->firstOrNew(['name' => $this->setting->value]);
        $this->before = $this->row->value;

        if ($this->leavesTheSecretAlone()) {
            return $this->row;
        }

        $this->update();
        $this->log();

        return $this->row;
    }

    /**
     * An empty secret means leave it as it is, never clear it, so a blank field
     * submitted with the rest of a form cannot silently drop a credential.
     * Clearing one is its own control, and its own action.
     */
    private function leavesTheSecretAlone(): bool
    {
        return $this->setting->kind()->isSecret() && ($this->value === null || $this->value === '');
    }

    private function validate(): void
    {
        if ($this->setting->fixedAtDeployment()) {
            throw ValidationException::withMessages([
                'value' => __('This is settled by the deployment and cannot be changed here.'),
            ]);
        }

        if (! $this->setting->providerIsInstalled()) {
            throw ValidationException::withMessages([
                'value' => __('This provider is unavailable on this server.'),
            ]);
        }

        Validator::make(
            ['value' => $this->value],
            ['value' => $this->setting->kind()->rules()],
        )->validate();

        $this->refuseWhenItWouldContradictAnotherSetting();
    }

    /**
     * Two settings mean nothing on their own: a default page size above the
     * ceiling would be brought down on every call, and a free storage limit
     * above the paid one would mean paying for less.
     */
    private function refuseWhenItWouldContradictAnotherSetting(): void
    {
        $pairs = [
            InstanceSettingEnum::ApiDefaultItemsPerPage->value => [InstanceSettingEnum::ApiMaximumItemsPerPage, true],
            InstanceSettingEnum::ApiMaximumItemsPerPage->value => [InstanceSettingEnum::ApiDefaultItemsPerPage, false],
            InstanceSettingEnum::FreeStorageLimitInBytes->value => [InstanceSettingEnum::PaidStorageLimitInBytes, true],
            InstanceSettingEnum::PaidStorageLimitInBytes->value => [InstanceSettingEnum::FreeStorageLimitInBytes, false],
        ];

        if (! array_key_exists($this->setting->value, $pairs)) {
            return;
        }

        [$other, $mustNotExceed] = $pairs[$this->setting->value];
        $mine = (int) $this->value;
        $theirs = (int) config($other->configKey());

        if ($mustNotExceed && $mine > $theirs) {
            throw ValidationException::withMessages([
                'value' => __('This cannot be above :other, which is :value.', [
                    'other' => __($other->description()),
                    'value' => $theirs,
                ]),
            ]);
        }

        if (! $mustNotExceed && $mine < $theirs) {
            throw ValidationException::withMessages([
                'value' => __('This cannot be below :other, which is :value.', [
                    'other' => __($other->description()),
                    'value' => $theirs,
                ]),
            ]);
        }
    }

    private function update(): void
    {
        $this->row->value = $this->setting->kind() === InstanceSettingKindEnum::Text
            ? TextSanitizer::nullablePlainText($this->value)
            : $this->value;
        $this->row->save();
    }

    /**
     * A secret's entry says that it changed and never what it changed to, so the
     * record does not become the one place a credential is readable in clear.
     */
    private function log(): void
    {
        $parameters = ['name' => __($this->setting->description())];

        if (! $this->setting->kind()->isSecret()) {
            $parameters['before'] = $this->before;
            $parameters['after'] = $this->row->value;
        }

        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::InstanceSettingUpdated,
            parameters: $parameters,
            subject: $this->row,
        )->onQueue('low');
    }
}
