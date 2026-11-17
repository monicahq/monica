<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ChangelogEntries;
use App\Http\Middleware\EnsureChangelogIsEnabled;
use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Docs\ApiDocsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogSwitchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_changelog_route_is_behind_the_switch(): void
    {
        $changelogRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => str_contains($route->uri(), 'changelog') && ! str_starts_with($route->uri(), 'docs/'));

        $this->assertCount(17, $changelogRoutes);

        foreach ($changelogRoutes as $route) {
            $this->assertContains(
                EnsureChangelogIsEnabled::class,
                app('router')->gatherRouteMiddleware($route),
                "The route `{$route->uri()}` answers while What’s new is switched off.",
            );
        }
    }

    #[Test]
    public function it_answers_not_found_everywhere_while_switched_off(): void
    {
        config(['monica.changelog_enabled' => false]);
        CarbonImmutable::setTestNow('2026-11-17 12:00:00');
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)->get(route('changelog.index'))->assertNotFound();
        $this->actingAs($michael)->get(route('changelog.comments.index', ['entry' => '01M7NBFWK5XNH908RFDGEAJXKN']))->assertNotFound();
        $this->actingAs($michael)->get(route('admin.changelog.index'))->assertNotFound();

        Sanctum::actingAs($michael);
        $this->json('GET', '/api/v1/changelog-entries')->assertNotFound();
    }

    #[Test]
    public function it_leaves_no_way_in_while_switched_off(): void
    {
        config(['monica.changelog_enabled' => false]);
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)->get(route('settings.index'))->assertDontSee('What’s new');
        $this->actingAs($michael)->get(route('admin.overview.index'))->assertDontSee(route('admin.changelog.index'), escape: false);
        $this->assertNotContains('What’s new', array_column(new ApiDocsViewModel()->groups(), 'name'));
    }
}
