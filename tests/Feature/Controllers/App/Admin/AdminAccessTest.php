<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function everyAdminUrl(): array
    {
        return [
            'the front door' => ['admin'],
            'the record' => ['admin/logs'],
            'the moderation queue' => ['admin/moderation'],
            'the support queue' => ['admin/support'],
            'the account lookup' => ['admin/accounts'],
            'the administrators' => ['admin/administrators'],
            'the configuration' => ['admin/configuration'],
            'the flags' => ['admin/flags'],
            'the search engine' => ['admin/search'],
        ];
    }

    #[Test]
    #[DataProvider('everyAdminUrl')]
    public function it_is_indistinguishable_from_nothing_for_somebody_not_signed_in(string $url): void
    {
        $this->get($url)->assertNotFound();
    }

    #[Test]
    #[DataProvider('everyAdminUrl')]
    public function it_is_indistinguishable_from_nothing_for_a_regular_user(string $url): void
    {
        $jim = User::factory()->create();

        $this->actingAs($jim)->get($url)->assertNotFound();
    }

    #[Test]
    #[DataProvider('everyAdminUrl')]
    public function it_is_closed_to_an_administrator_who_can_no_longer_sign_in(string $url): void
    {
        $michael = User::factory()->create(['is_active' => false]);
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)->get($url)->assertNotFound();
    }

    #[Test]
    #[DataProvider('everyAdminUrl')]
    public function it_opens_for_an_administrator(string $url): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $response = $this->actingAs($michael)->get($url);

        $this->assertNotEquals(404, $response->status());
    }

    #[Test]
    public function an_administrator_is_offered_the_way_in_from_the_ordinary_application(): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->get(route('vaults.index'))
            ->assertSee(route('admin.overview.index'), escape: false);
    }

    #[Test]
    public function a_regular_user_is_offered_no_way_in_and_no_hint_that_there_is_one(): void
    {
        $jim = User::factory()->create();

        $this->actingAs($jim)
            ->get(route('vaults.index'))
            ->assertDontSee('Admin space')
            ->assertDontSee('/admin', escape: false);
    }
}
