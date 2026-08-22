<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Account;
use App\Models\Administrator;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();

        $this->actingAs($jim)->get(route('admin.overview.index'))->assertNotFound();
    }

    #[Test]
    public function it_is_the_first_thing_an_administrator_sees(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get('admin');

        $response->assertStatus(200);
        $response->assertSee('Overview', escape: false);
    }

    #[Test]
    public function it_says_what_period_every_figure_covers(): void
    {
        $michael = $this->administrator();
        Account::factory()->count(2)->create(['storage_used_in_bytes' => 1024 * 1024]);

        $response = $this->actingAs($michael)->get(route('admin.overview.index'));

        $response->assertSee('all time', escape: false);
        $response->assertSee('last 7 days', escape: false);
        $response->assertSee('last 30 days', escape: false);
        $response->assertSee('right now', escape: false);
        $response->assertSee('2 MB', escape: false);
    }

    #[Test]
    public function it_reads_as_an_empty_instance_rather_than_as_a_broken_one(): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);
        Account::query()->whereKeyNot($michael->account_id)->delete();

        $response = $this->actingAs($michael)->get(route('admin.overview.index'));

        $response->assertStatus(200);
        $response->assertSee('0 B', escape: false);
    }

    #[Test]
    public function it_leads_to_the_moderation_queue_in_one_step(): void
    {
        $michael = $this->administrator();
        MarketplaceListingVersion::factory()->create();

        $response = $this->actingAs($michael)->get(route('admin.overview.index'));

        $response->assertSee('1 marketplace submission is waiting for a decision.', escape: false);
        $response->assertSee(route('admin.moderation.index'), escape: false);
    }

    #[Test]
    public function it_says_nothing_about_a_queue_that_is_empty(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        $response->assertDontSee('waiting for a decision', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
