<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_purges_a_flag_no_longer_declared_and_keeps_the_declared_ones(): void
    {
        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');
        $pam->account->features()->activate('retired-capability');

        $this->artisan('pennant:purge', ['--except-registered' => true])->assertExitCode(0);

        $this->assertDatabaseMissing('features', ['name' => 'retired-capability']);
        $this->assertDatabaseHas('features', ['name' => 'example-capability']);
    }

    #[Test]
    public function it_remembers_a_browser_for_the_duration_the_instance_sets(): void
    {
        $this->assertSame((int) config('monica.remember_duration_days') * 24 * 60, config('auth.guards.web.remember'));
    }

    #[Test]
    public function it_keeps_the_duration_on_a_guard_built_again(): void
    {
        $this->app['auth']->forgetGuards();

        Auth::guard('web')->login(User::factory()->create(), remember: true);

        $cookie = Cookie::queued(Auth::guard('web')->getRecallerName());

        $this->assertEqualsWithDelta(
            now()->addDays((int) config('monica.remember_duration_days'))->getTimestamp(),
            $cookie->getExpiresTime(),
            60,
        );
    }

    #[Test]
    public function it_registers_the_collation_macro_on_collections(): void
    {
        $this->assertTrue(Collection::hasMacro('sortByCollator'));
    }

    #[Test]
    public function it_orders_a_collection_by_a_string_key_through_the_macro(): void
    {
        $sorted = new Collection([
            ['name' => 'Schrute'],
            ['name' => 'Élodie'],
            ['name' => 'Erin'],
        ])->sortByCollator('name');

        $this->assertSame(['Élodie', 'Erin', 'Schrute'], $sorted->pluck('name')->all());
    }

    #[Test]
    public function it_orders_a_collection_by_a_callable_through_the_macro(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame(['Élodie', 'Erin', 'Schrute'], $sorted->all());
    }

    #[Test]
    public function it_orders_a_collection_the_other_way_round_through_the_macro(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name, descending: true);

        $this->assertSame(['Schrute', 'Erin', 'Élodie'], $sorted->all());
    }

    #[Test]
    public function it_compares_french_accents_from_the_end_of_the_word_through_the_macro(): void
    {
        $this->app->setLocale('fr_FR');

        $sorted = new Collection(['côté', 'coté', 'côte', 'cote'])
            ->sortByCollator(fn (string $word): string => $word);

        $this->assertSame(['cote', 'côte', 'coté', 'côté'], $sorted->all());
    }

    #[Test]
    public function it_reaches_an_eloquent_collection_and_hands_one_back(): void
    {
        User::factory()->create(['first_name' => 'Zoe']);
        User::factory()->create(['first_name' => 'Élodie']);
        User::factory()->create(['first_name' => 'Erin']);

        $sorted = User::query()->get()->sortByCollator('first_name');

        $this->assertInstanceOf(EloquentCollection::class, $sorted);
        $this->assertSame(['Élodie', 'Erin', 'Zoe'], $sorted->pluck('first_name')->all());
    }

    #[Test]
    public function it_reindexes_the_collection_the_macro_hands_back(): void
    {
        $sorted = new Collection(['Schrute', 'Élodie', 'Erin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame([0, 1, 2], $sorted->keys()->all());
    }

    #[Test]
    public function it_files_a_letter_carrying_a_ring_with_its_plain_letter(): void
    {
        $sorted = new Collection(['Scott', 'Ångström', 'Martin'])
            ->sortByCollator(fn (string $name): string => $name);

        $this->assertSame(['Ångström', 'Martin', 'Scott'], $sorted->all());
    }
}
