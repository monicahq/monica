<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Logs;

use App\Enums\UserActionEnum;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_logs_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserProfileUpdate->value,
            'parameters' => ['name' => 'Michael Scott'],
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('user_profile_updated', escape: false);
        $response->assertSee('Updated the profile of Michael Scott', escape: false);
    }

    #[Test]
    public function it_shows_the_address_each_action_came_from(): void
    {
        $user = User::factory()->create();

        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'ip_address' => '198.51.100.24',
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('198.51.100.24', escape: false);
    }

    #[Test]
    public function it_shows_no_address_for_an_action_that_came_from_nowhere(): void
    {
        $user = User::factory()->create();

        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'ip_address' => null,
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('No address, so this was the application itself', escape: false);
    }

    #[Test]
    public function it_shows_a_blank_state_when_there_is_nothing_to_read_yet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Nothing has happened yet', escape: false);
        $response->assertSee('is written down here', escape: false);
        $response->assertSee('No email has been sent yet', escape: false);
        $response->assertSee('with whether they arrived', escape: false);
    }

    #[Test]
    public function it_offers_to_load_more_when_a_page_is_not_enough(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(11)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Load more', escape: false);
        $response->assertSee('id="pagination"', escape: false);
    }

    #[Test]
    public function it_stops_offering_to_load_more_on_the_last_page(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(3)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Load more', escape: false);
    }

    #[Test]
    public function it_shows_the_latest_emails_sent_and_offers_to_browse_the_rest(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(7)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'sent_at' => now()->subWeek(),
        ]);
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email_address' => 'creed.bratton@dundermifflin.com',
            'subject' => 'Confirm your email address',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('creed.bratton@dundermifflin.com', escape: false);
        $response->assertSee('Confirm your email address', escape: false);
        $response->assertSee('Browse all emails', escape: false);
        $response->assertSee(route('settings.emailsSent.index'), escape: false);
    }
}
