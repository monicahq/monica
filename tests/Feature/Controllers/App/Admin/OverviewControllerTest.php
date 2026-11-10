<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_the_first_thing_an_administrator_sees(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get('admin');

        $response->assertStatus(200);
        $response->assertSee('Overview', escape: false);
    }

    #[Test]
    public function it_leads_to_the_moderation_queue_in_one_step(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        $response->assertSeeInOrder(['href="'.route('admin.moderation.index').'"', 'Open moderation'], escape: false);
    }

    #[Test]
    public function it_offers_to_count_again(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        $response->assertSee('Counted at', escape: false);
        $response->assertSeeInOrder(['href="'.route('admin.overview.index').'"', 'Count again'], escape: false);
    }

    #[Test]
    public function it_shows_how_many_emails_left_and_where_they_got_to(): void
    {
        $michael = $this->administrator();
        EmailSent::factory()->count(2)->delivered()->create();
        EmailSent::factory()->bounced()->create();
        EmailSent::factory()->complained()->create();

        $response = $this->actingAs($michael)->get(route('admin.overview.index'));

        $response->assertStatus(200);
        $response->assertSee('Emails sent', escape: false);
        $response->assertSee('Where the emails got to', escape: false);
        $response->assertSee('Delivered', escape: false);
        $response->assertSee('Bounced', escape: false);
        $response->assertSee('Marked as spam', escape: false);
        $response->assertSee('On its way', escape: false);
    }

    #[Test]
    public function it_names_each_state_in_words_so_none_of_them_is_carried_by_colour(): void
    {
        EmailSent::factory()->complained()->create();

        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        foreach (['Delivered', 'Bounced', 'Marked as spam', 'On its way'] as $word) {
            $response->assertSee($word, escape: false);
        }
    }

    #[Test]
    public function it_shows_the_email_breakdown_on_an_instance_that_has_sent_none(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        $response->assertStatus(200);
        $response->assertSee('Where the emails got to', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
