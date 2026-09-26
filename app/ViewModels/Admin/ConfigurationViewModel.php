<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\InstanceSettingEnum;
use App\Enums\InstanceSettingGroupEnum;
use App\Helpers\InstanceSettings;
use App\Helpers\TelegramWebhook;
use App\Models\InstanceSetting;
use App\Models\TelegramWebhookRegistration;

/**
 * What the configuration screen of the admin space shows.
 */
class ConfigurationViewModel
{
    /** @return array<string, string|null> */
    private function stored(): array
    {
        return once(fn (): array => InstanceSetting::query()->pluck('value', 'name')->all());
    }

    /**
     * Every setting, in its group, in the order the catalogue declares it.
     *
     * @return array<int, array{key: string, label: string, settings: array<int, array<string, mixed>>}>
     */
    public function groups(): array
    {
        return once(function (): array {
            $groups = [];

            foreach (InstanceSettingGroupEnum::cases() as $group) {
                $settings = array_values(array_filter(
                    InstanceSettingEnum::cases(),
                    fn (InstanceSettingEnum $setting): bool => $setting->group() === $group,
                ));

                $groups[] = [
                    'key' => $group->value,
                    'label' => __($group->label()),
                    'settings' => array_map(fn (InstanceSettingEnum $s): array => $this->setting($s), $settings),
                ];
            }

            return $groups;
        });
    }

    /**
     * Replacing a Telegram credential only takes something away while a
     * registration holds, so the warning is dropped when there is none.
     *
     * @return array<string, mixed>
     */
    private function setting(InstanceSettingEnum $setting): array
    {
        $described = InstanceSettings::describe($setting, $this->stored());

        if ($setting->warnsWhen() === 'replaced' && ! $this->telegramRegistrationHolds()) {
            $described['warning'] = null;
        }

        return $described;
    }

    private function telegramRegistrationHolds(): bool
    {
        return once(fn (): bool => TelegramWebhook::isCurrent(TelegramWebhookRegistration::query()->first()));
    }

    /**
     * Rows for settings the product no longer carries. They are kept and shown
     * rather than dropped, so an upgrade never takes a decision away silently.
     *
     * @return array<int, array{name: string}>
     */
    public function orphaned(): array
    {
        return once(function (): array {
            $known = array_map(fn (InstanceSettingEnum $s): string => $s->value, InstanceSettingEnum::cases());

            return array_values(array_map(
                fn (string $name): array => ['name' => $name],
                array_filter(array_keys($this->stored()), fn (string $name): bool => ! in_array($name, $known, true)),
            ));
        });
    }

    public function hasOrphaned(): bool
    {
        return $this->orphaned() !== [];
    }

    public function telegram(): TelegramRegistrationPanel
    {
        return new TelegramRegistrationPanel;
    }
}
