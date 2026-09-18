<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\InstanceSettingEnum;
use App\Models\InstanceSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Put whatever an administrator chose in front of what the deployment supplied.
 *
 * This exists so the ninety odd call sites that read configuration keep reading
 * it and know nothing about the table behind it. It is a helper rather than two
 * copies in the provider and the queue listener, because a divergence between
 * those two would mean a queued job obeying different values from the request
 * that dispatched it, which no test would catch.
 */
class InstanceSettings
{
    /**
     * What is stored, keyed by setting name. Cached for a minute so a worker
     * that booted long ago is never staler than that, and forgotten by the model
     * on write so the next web request sees a change at once.
     *
     * A failure answers as though nothing were stored. This runs on every boot,
     * including the one that creates the table.
     *
     * @return array<string, string|null>
     */
    public static function stored(): array
    {
        try {
            return Cache::remember(
                InstanceSetting::CACHE_KEY,
                InstanceSetting::CACHE_SECONDS,
                fn (): array => InstanceSetting::query()->pluck('value', 'name')->all(),
            );
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Record what configuration holds before anything overrides it. That is the
     * deployment value: what the screen shows, what reverting restores, and what
     * it names when a stored value disagrees with it. After `config()->set()` it
     * is otherwise unrecoverable, and `env()` may not be read outside `config/`.
     */
    public static function stashDeploymentValues(): void
    {
        foreach (InstanceSettingEnum::cases() as $setting) {
            config()->set("instance_deployment.{$setting->value}", config($setting->configKey()));
        }
    }

    /**
     * Point every changeable key at the stored value, or back at the stashed
     * deployment value when nothing is stored. Reading the stash rather than
     * leaving the key alone is what makes this safe to call again on a process
     * that has already run it.
     */
    public static function apply(): void
    {
        $stored = self::stored();

        foreach (InstanceSettingEnum::cases() as $setting) {
            if ($setting->fixedAtDeployment()) {
                continue;
            }

            config()->set($setting->configKey(), array_key_exists($setting->value, $stored)
                ? $setting->kind()->cast($stored[$setting->value])
                : config("instance_deployment.{$setting->value}"));
        }
    }

    /**
     * Everything that is true of one setting right now: its value, where that
     * value came from, whether somebody changed it, and whether it can be
     * changed at all.
     *
     * It lives here rather than in the view model because the JSON API needs the
     * same answers, and the constitution keeps a view model out of everything
     * but Blade. Two copies of this would be two surfaces disagreeing about
     * whether a setting is changeable.
     *
     * A secret never carries its value, on either surface. All that is said
     * about it is whether one is set.
     *
     * @param  array<string, string|null>  $stored
     * @return array<string, mixed>
     */
    public static function describe(InstanceSettingEnum $setting, array $stored): array
    {
        $isStored = array_key_exists($setting->value, $stored);
        $deployment = config("instance_deployment.{$setting->value}");
        $isSecret = $setting->kind()->isSecret();
        $providerIsAbsent = ! $setting->providerIsInstalled();

        return [
            'name' => $setting->value,
            'group' => $setting->group()->value,
            'description' => __($setting->description()),
            'environmentVariable' => $setting->environmentVariable(),
            'kind' => $setting->kind()->value,
            'value' => $isSecret ? null : config($setting->configKey()),
            'deploymentValue' => $isSecret ? null : $deployment,
            'secretIsSet' => $isSecret && ($isStored || ($deployment !== null && $deployment !== '')),
            'changed' => $isStored && ! $setting->fixedAtDeployment(),
            'changeable' => ! $setting->fixedAtDeployment() && ! $providerIsAbsent,
            'reason' => self::reason($setting, $providerIsAbsent),
            'warning' => $setting->warning() === null ? null : __($setting->warning()),
            'warnsWhen' => $setting->warnsWhen(),
            'disagreesWithDeployment' => ! $isSecret
                && $isStored
                && ! $setting->fixedAtDeployment()
                && (string) $deployment !== (string) $stored[$setting->value],
        ];
    }

    /**
     * Why a control is not activable, or null when it is. A refused control
     * stays on the screen and says why rather than disappearing.
     */
    private static function reason(InstanceSettingEnum $setting, bool $providerIsAbsent): ?string
    {
        if ($setting->fixedAtDeployment()) {
            return __('Settled by the deployment of this instance.');
        }

        if ($providerIsAbsent) {
            return __('Unavailable on this server, because its package is not installed.');
        }

        return null;
    }
}
