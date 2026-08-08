<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Logs;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_emails_sent_screen(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
        EmailSent::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'email_address' => 'pam.beesly@dundermifflin.com',
            'subject' => 'A sign-in from a new place',
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('pam.beesly@dundermifflin.com', escape: false);
        $response->assertSee('A sign-in from a new place', escape: false);
        $response->assertSee('Pam Beesly', escape: false);
    }

    #[Test]
    public function it_shows_a_blank_state_when_nothing_was_ever_sent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('No emails yet', escape: false);
        $response->assertSee('Nothing has left our hands yet.', escape: false);
    }

    #[Test]
    public function it_hides_the_emails_sent_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $stranger->account_id,
            'user_id' => $stranger->id,
            'subject' => 'Your magic link',
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Your magic link', escape: false);
    }

    #[Test]
    public function it_offers_to_load_more_when_a_page_is_not_enough(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(11)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Load more', escape: false);
        $response->assertSee('id="pagination"', escape: false);
    }

    #[Test]
    public function it_stops_offering_to_load_more_on_the_last_page(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(10)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Load more', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.emailsSent.index'));

        $response->assertRedirect(route('auth.login.new'));
    }
}
