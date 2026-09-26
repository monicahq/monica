<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateTelegramWebhookSecret;
use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateTelegramWebhookSecretTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_a_strong_random_secret(): void
    {
        Queue::fake();

        $setting = new GenerateTelegramWebhookSecret(administrator: User::factory()->create())->execute();

        $this->assertSame(InstanceSettingEnum::TelegramBotWebhookSecret->value, $setting->name);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $setting->fresh()->value);
    }

    #[Test]
    public function it_replaces_the_secret_already_set(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create(['name' => InstanceSettingEnum::TelegramBotWebhookSecret->value, 'value' => 'dwight-schrute']);

        new GenerateTelegramWebhookSecret(administrator: User::factory()->create())->execute();

        $this->assertDatabaseCount('instance_settings', 1);
        $this->assertNotSame('dwight-schrute', InstanceSetting::query()->first()->value);
    }

    #[Test]
    public function it_logs_the_change_without_the_secret(): void
    {
        Queue::fake();

        new GenerateTelegramWebhookSecret(administrator: User::factory()->create())->execute();

        Queue::assertPushed(LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::InstanceSettingUpdated
            && ! array_key_exists('after', $job->parameters ?? []));
    }
}
