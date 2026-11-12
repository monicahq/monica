<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'channel',
            'label',
            'is_enabled',
            'destination',
            'needs_destination',
            'requires_proof',
            'is_proven',
            'is_configured_on_the_instance',
            'disabled_reason',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_every_channel_and_its_state(): void
    {
        $this->signedIn();

        $response = $this->json('GET', route('api.v1.notificationChannels'));

        $response->assertOk();
        $response->assertJsonStructure(['data' => [$this->jsonStructure]]);
        $response->assertJsonCount(4, 'data');
    }

    #[Test]
    public function it_returns_one_by_its_name(): void
    {
        $this->signedIn();

        $response = $this->json('GET', route('api.v1.notificationChannels.show', 'sms'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.channel', 'sms');
        $response->assertJsonPath('data.attributes.needs_destination', true);
        $response->assertJsonPath('data.attributes.requires_proof', true);
    }

    #[Test]
    public function it_switches_a_channel_on(): void
    {
        $user = $this->signedIn();
        $this->channel($user, NotificationChannelEnum::Sms)->fill([
            'destination' => '+15705550172',
            'proven_at' => now(),
        ])->save();

        $response = $this->json('PUT', route('api.v1.notificationChannels.update', 'sms'), [
            'is_enabled' => true,
            'destination' => '+15705550172',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_enabled', true);
        $this->assertTrue($this->channel($user, NotificationChannelEnum::Sms)->is_enabled);
    }

    #[Test]
    public function it_refuses_to_switch_on_a_channel_with_nothing_behind_it(): void
    {
        $this->signedIn();

        $response = $this->json('PUT', route('api.v1.notificationChannels.update', 'sms'), ['is_enabled' => true]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('is_enabled');
    }

    #[Test]
    public function it_refuses_a_destination_longer_than_its_box_before_sending_a_code(): void
    {
        NotificationFacade::fake();
        $user = $this->signedIn();
        $this->channel($user, NotificationChannelEnum::Sms)->fill([
            'destination' => '+15705550172',
        ])->save();

        $response = $this->json('POST', route('api.v1.notificationChannels.proof.store', 'sms'), ['destination' => str_repeat('1', 256)]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('destination');
        $this->assertSame('+15705550172', $this->channel($user, NotificationChannelEnum::Sms)->destination);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function it_sends_a_code_and_takes_it_back(): void
    {
        NotificationFacade::fake();
        $user = $this->signedIn();
        $this->channel($user, NotificationChannelEnum::Sms)->fill([
            'destination' => '+15705550172',
        ])->save();

        $this->json('POST', route('api.v1.notificationChannels.proof.store', 'sms'))->assertCreated();

        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->json('PUT', route('api.v1.notificationChannels.proof.update', 'sms'), ['code' => '123456']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_proven', true);
    }

    #[Test]
    public function it_sends_a_test_message_and_says_it_got_through(): void
    {
        NotificationFacade::fake();
        $user = $this->signedIn();
        $this->channel($user, NotificationChannelEnum::Sms)->fill([
            'is_enabled' => true,
            'destination' => '+15705550172',
            'proven_at' => now(),
        ])->save();

        $response = $this->json('POST', route('api.v1.notificationChannels.test.store', 'sms'));

        $response->assertCreated();
        $response->assertJsonPath('data.sent', true);
    }

    #[Test]
    public function it_says_whether_this_server_can_send_on_the_channel(): void
    {
        $this->signedIn();

        $this->json('GET', route('api.v1.notificationChannels.show', 'sms'))
            ->assertJsonPath('data.attributes.is_configured_on_the_instance', true);

        config(['vonage.api_key' => null]);

        $this->json('GET', route('api.v1.notificationChannels.show', 'sms'))
            ->assertJsonPath('data.attributes.is_configured_on_the_instance', false);
    }

    #[Test]
    public function an_unknown_channel_is_not_found(): void
    {
        $this->signedIn();

        $this->json('GET', route('api.v1.notificationChannels.show', 'carrier-pigeon'))->assertNotFound();
    }

    #[Test]
    public function it_never_lists_another_accounts_channels(): void
    {
        $this->signedIn();
        Account::factory()->create();

        $this->json('GET', route('api.v1.notificationChannels'))->assertJsonCount(4, 'data');
    }

    #[Test]
    public function it_leaves_text_messages_out_while_sms_notifications_is_off_for_the_account(): void
    {
        $user = $this->signedIn();
        $user->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->json('GET', route('api.v1.notificationChannels'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['id' => 'sms']);

        $this->json('GET', route('api.v1.notificationChannels.show', 'sms'))->assertNotFound();
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function channel(User $user, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', $channel->value)
            ->firstOrFail();
    }
}
