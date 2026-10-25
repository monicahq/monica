<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelSetupControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'channel',
            'is_enabled',
            'destination',
            'is_proven',
            'can_send_proof_code',
            'proof_code_refusal',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_removes_the_number_and_answers_with_the_channel(): void
    {
        $user = $this->signedInWithNumber();

        $response = $this->json('DELETE', route('api.v1.notificationChannels.setup.destroy', 'sms'));

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.destination', null);
        $response->assertJsonPath('data.attributes.is_enabled', false);
        $this->assertNull($this->channel($user)->destination);
    }

    #[Test]
    public function email_is_refused_because_it_has_nothing_to_remove(): void
    {
        $this->signedInWithNumber();

        $response = $this->json('DELETE', route('api.v1.notificationChannels.setup.destroy', 'email'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['channel' => 'This channel has nothing to remove.']);
    }

    #[Test]
    public function an_unknown_channel_is_not_found(): void
    {
        $this->signedInWithNumber();

        $this->json('DELETE', route('api.v1.notificationChannels.setup.destroy', 'carrier-pigeon'))->assertNotFound();
    }

    #[Test]
    public function the_number_of_another_account_is_left_alone(): void
    {
        $theirs = $this->signedInWithNumber();
        $this->signedInWithNumber();

        $this->json('DELETE', route('api.v1.notificationChannels.setup.destroy', 'sms'))->assertOk();

        $this->assertSame('+15705550172', $this->channel($theirs)->destination);
    }

    #[Test]
    public function a_number_kept_while_text_messages_are_withdrawn_is_removed_and_the_channel_stays_hidden(): void
    {
        $user = $this->signedInWithNumber();
        $user->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->json('DELETE', route('api.v1.notificationChannels.setup.destroy', 'sms'))->assertOk();

        $this->assertNull($this->channel($user)->destination);
        $this->json('GET', route('api.v1.notificationChannels.show', 'sms'))->assertNotFound();
    }

    private function signedInWithNumber(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        $this->channel($user)->fill([
            'destination' => '+15705550172',
        ])->save();

        return $user;
    }

    private function channel(User $user): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->firstOrFail();
    }
}
