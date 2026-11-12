<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelProofCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelProofControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_code_to_the_number(): void
    {
        NotificationFacade::fake();
        $user = $this->readyTextMessage();

        $response = $this->post(route('settings.notifications.proof.store', 'sms'));

        $response->assertRedirect(route('settings.notifications.index'));
        $response->assertSessionHas('status', 'A code is on its way. Type it back here.');
        NotificationFacade::assertSentTo($user, ChannelProofCode::class);
    }

    #[Test]
    public function it_refuses_a_destination_longer_than_its_box(): void
    {
        NotificationFacade::fake();
        $user = $this->readyTextMessage();

        $response = $this->post(route('settings.notifications.proof.store', 'sms'), ['destination' => str_repeat('1', 256)]);

        $response->assertSessionHasErrors('destination');
        $this->assertSame('+15705550172', $this->channel($user)->destination);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function typing_the_code_back_proves_the_number(): void
    {
        $user = $this->readyTextMessage();
        $this->channel($user)->update([
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->put(route('settings.notifications.proof.update', 'sms'), ['code' => '123456']);

        $response->assertRedirect(route('settings.notifications.index'));
        $this->assertNotNull($this->channel($user)->proven_at);
    }

    #[Test]
    public function a_wrong_code_is_refused_and_says_which_of_the_three_it_is(): void
    {
        $user = $this->readyTextMessage();
        $this->channel($user)->update([
            'proof_code_hash' => Hash::make('123456'),
            'proof_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->put(route('settings.notifications.proof.update', 'sms'), ['code' => '000000']);

        $response->assertSessionHasErrors(['code' => 'That is not the code we sent.']);
    }

    #[Test]
    public function asking_again_inside_the_wait_is_refused_with_the_time_left(): void
    {
        NotificationFacade::fake();
        $this->freezeTime();
        $user = $this->readyTextMessage();
        $this->channel($user)->update(['proof_sent_count' => 1, 'proof_last_sent_at' => now()->subSeconds(45)]);

        $response = $this->post(route('settings.notifications.proof.store', 'sms'));

        $response->assertSessionHasErrors(['proof' => 'You can ask for another code in 15 seconds.']);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function the_screen_keeps_the_refused_control_and_points_it_at_its_reason(): void
    {
        $this->freezeTime();
        $user = $this->readyTextMessage();
        $this->channel($user)->update(['proof_sent_count' => 1, 'proof_last_sent_at' => now()->subSeconds(45)]);

        $response = $this->get(route('settings.notifications.index'));

        $response->assertOk();
        $response->assertSee('aria-disabled="true"', escape: false);
        $response->assertSee('aria-describedby="sms-proof-code-refusal"', escape: false);
        $response->assertSee('id="sms-proof-code-refusal"', escape: false);
        $response->assertSee('You can ask for another code in 15 seconds.');
    }

    private function readyTextMessage(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

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
