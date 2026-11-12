<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Features\ExampleCapability;
use Tests\TestCase;

class EnsureFlagIsOnTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));

        Route::middleware(['web', 'auth', 'flag:example-capability'])
            ->get('example-capability', fn (): string => 'Dunder Mifflin Infinity');
        Route::middleware(['api', 'auth:sanctum', 'flag:example-capability'])
            ->get('api/example-capability', fn (): array => ['name' => 'Dunder Mifflin Infinity']);
        Route::middleware(['web', 'flag:example-capability'])
            ->get('public-example-capability', fn (): string => 'Dunder Mifflin Infinity');
    }

    #[Test]
    public function it_shows_a_visitor_a_public_page_exactly_when_it_is_on_for_every_account(): void
    {
        $this->get('public-example-capability')->assertNotFound();

        Feature::for(null)->activate(ExampleCapability::class);
        Feature::flushCache();

        $this->get('public-example-capability')->assertOk();
    }

    #[Test]
    public function it_keeps_a_switch_for_one_account_from_reaching_visitors(): void
    {
        $jim = User::factory()->create();
        $jim->account->features()->activate(ExampleCapability::class);

        $this->actingAs($jim)->get('public-example-capability')->assertOk();

        auth()->logout();

        $this->get('public-example-capability')->assertNotFound();
    }

    #[Test]
    public function it_answers_the_account_the_flag_is_on_for_and_nobody_else(): void
    {
        $jim = User::factory()->create();
        $dwight = User::factory()->create();
        $jim->account->features()->activate(ExampleCapability::class);

        $this->actingAs($jim)->get('example-capability')->assertOk()->assertSee('Dunder Mifflin Infinity');
        $this->actingAs($dwight)->get('example-capability')->assertNotFound();
    }

    #[Test]
    public function it_gates_an_api_route_the_same_way(): void
    {
        $jim = User::factory()->create();
        $dwight = User::factory()->create();
        $jim->account->features()->activate(ExampleCapability::class);

        Sanctum::actingAs($dwight);
        $this->json('GET', 'api/example-capability')->assertNotFound();

        Sanctum::actingAs($jim);
        $this->json('GET', 'api/example-capability')->assertOk()->assertJsonPath('name', 'Dunder Mifflin Infinity');
    }
}
