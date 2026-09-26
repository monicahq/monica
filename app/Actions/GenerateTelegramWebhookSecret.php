<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InstanceSettingEnum;
use App\Models\InstanceSetting;
use App\Models\User;

/**
 * Store a new random secret for Telegram to prove its calls with, so an
 * administrator never has to make one in a terminal. It is saved like any other
 * setting, and so logged without its value.
 */
class GenerateTelegramWebhookSecret
{
    public function __construct(
        private readonly User $administrator,
    ) {}

    public function execute(): InstanceSetting
    {
        return new UpdateInstanceSetting(
            administrator: $this->administrator,
            setting: InstanceSettingEnum::TelegramBotWebhookSecret,
            value: bin2hex(random_bytes(32)),
        )->execute();
    }
}
