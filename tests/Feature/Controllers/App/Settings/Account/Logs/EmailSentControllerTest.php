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
        $response->assertSee(route('settings.logs.index'), escape: false);
    }

    #[Test]
    public function it_shows_a_blank_state_when_nothing_was_ever_sent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('No email has been sent yet', escape: false);
        $response->assertSee('with whether they arrived', escape: false);
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
    public function it_badges_an_email_that_is_still_on_its_way(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('On its way', escape: false);
        $response->assertDontSee('Delivered', escape: false);
        $response->assertDontSee('Bounced', escape: false);
    }

    #[Test]
    public function it_badges_an_email_that_arrived(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->delivered()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Delivered', escape: false);
        $response->assertDontSee('On its way', escape: false);
    }

    #[Test]
    public function it_badges_an_email_that_came_back(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->bounced()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Bounced', escape: false);
        $response->assertDontSee('On its way', escape: false);
    }

    #[Test]
    public function it_badges_an_email_that_came_back_after_it_was_reported_delivered(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->delivered()->bounced()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Bounced', escape: false);
        $response->assertDontSee('Delivered', escape: false);
    }

    #[Test]
    public function it_badges_an_email_the_recipient_marked_as_spam(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->complained()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Marked as spam', escape: false);
        $response->assertDontSee('On its way', escape: false);
    }

    #[Test]
    public function it_badges_a_complaint_over_a_bounce_and_a_delivery(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->delivered()->bounced()->complained()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.emailsSent.index'));

        $response->assertStatus(200);
        $response->assertSee('Marked as spam', escape: false);
        $response->assertDontSee('>Bounced<', escape: false);
    }
}
