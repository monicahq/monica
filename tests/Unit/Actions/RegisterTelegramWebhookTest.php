<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RegisterTelegramWebhook;
use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\TelegramWebhookRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::preventStrayRequests();
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_tells_telegram_the_address_and_the_secret(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true, 'description' => 'Webhook was set'])]);
        $michael = User::factory()->create();

        $registration = new RegisterTelegramWebhook(administrator: $michael)->execute();

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bottesting-bot-token/setWebhook'
            && $request['url'] === 'https://monica.dundermifflin.com/telegram/webhook'
            && $request['secret_token'] === 'testing-webhook-secret');
        $this->assertInstanceOf(TelegramWebhookRegistration::class, $registration);
        $this->assertDatabaseHas('telegram_webhook_registrations', [
            'user_id' => $michael->id,
            'url' => 'https://monica.dundermifflin.com/telegram/webhook',
        ]);

        $telegramWebhookRegistration = TelegramWebhookRegistration::query()->where('user_id', $michael->id)->first();
        $this->assertTrue(Hash::check('testing-bot-token', $telegramWebhookRegistration->token_fingerprint));
        $this->assertTrue(Hash::check('testing-webhook-secret', $telegramWebhookRegistration->secret_fingerprint));

        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::TelegramWebhookRegistered
            && $job->parameters === ['url' => 'https://monica.dundermifflin.com/telegram/webhook']);
    }

    #[Test]
    public function it_updates_the_registration_instead_of_adding_one(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);
        TelegramWebhookRegistration::factory()->create(['url' => 'https://stamford.dundermifflin.com/telegram/webhook']);

        new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();

        $this->assertDatabaseCount('telegram_webhook_registrations', 1);
        $this->assertDatabaseHas('telegram_webhook_registrations', ['url' => 'https://monica.dundermifflin.com/telegram/webhook']);
    }

    #[Test]
    public function it_refuses_without_calling_telegram_when_a_prerequisite_is_missing(): void
    {
        Http::fake();
        config(['services.telegram.webhook_secret' => null]);

        try {
            new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();
            $this->fail('The registration went through without a secret.');
        } catch (ValidationException $e) {
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_webhook_registrations', 0);
    }

    #[Test]
    public function it_refuses_with_telegrams_reason(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401)]);

        try {
            new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();
            $this->fail('A refusal from Telegram was taken for a registration.');
        } catch (ValidationException $e) {
        }

        $this->assertDatabaseCount('telegram_webhook_registrations', 0);
        Queue::assertNotPushed(LogAdministratorAction::class);
    }

    #[Test]
    public function it_says_telegram_could_not_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->expectException(ValidationException::class);

        new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();
    }

    #[Test]
    public function it_records_the_console_as_the_author(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

        new RegisterTelegramWebhook(administrator: null)->execute();

        $this->assertDatabaseHas('telegram_webhook_registrations', ['user_id' => null]);
        Queue::assertPushed(LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->administrator === null);
    }

    #[Test]
    public function it_fills_the_bot_address_when_none_is_set(): void
    {
        config(['services.telegram.bot_url' => null]);
        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['id' => 1, 'is_bot' => true, 'username' => 'DunderMifflinBot']]),
        ]);

        new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();

        $this->assertSame(
            'https://t.me/DunderMifflinBot',
            InstanceSetting::query()->where('name', InstanceSettingEnum::TelegramBotUrl->value)->value('value'),
        );
    }

    #[Test]
    public function it_leaves_a_bot_address_already_set_and_says_it_differs(): void
    {
        config(['services.telegram.bot_url' => 'https://t.me/SabreBot']);
        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'DunderMifflinBot']]),
        ]);

        $action = new RegisterTelegramWebhook(administrator: User::factory()->create());
        $action->execute();

        $this->assertDatabaseMissing('instance_settings', ['name' => InstanceSettingEnum::TelegramBotUrl->value]);
        $this->assertSame('https://t.me/DunderMifflinBot', $action->differingBotAddress);
    }

    #[Test]
    public function it_keeps_the_registration_when_telegram_does_not_describe_the_bot(): void
    {
        config(['services.telegram.bot_url' => null]);
        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/getMe' => Http::response(['ok' => false, 'description' => 'Internal Server Error'], 500),
        ]);

        new RegisterTelegramWebhook(administrator: User::factory()->create())->execute();

        $this->assertDatabaseCount('telegram_webhook_registrations', 1);
        $this->assertDatabaseMissing('instance_settings', ['name' => InstanceSettingEnum::TelegramBotUrl->value]);
    }
}
