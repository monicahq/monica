<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureSupportIsEnabled;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportSwitchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_support_route_is_behind_the_switch(): void
    {
        $supportRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => str_contains($route->uri(), 'support'));

        $this->assertCount(25, $supportRoutes);

        foreach ($supportRoutes as $route) {
            $this->assertContains(
                EnsureSupportIsEnabled::class,
                app('router')->gatherRouteMiddleware($route),
                "The route `{$route->uri()}` answers while support is switched off.",
            );
        }
    }

    #[Test]
    public function it_answers_not_found_everywhere_while_support_is_switched_off(): void
    {
        config(['monica.support_enabled' => false]);
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);
        $supportRequest = SupportRequest::factory()->create(['account_id' => $michael->account_id]);

        $this->actingAs($michael)->get(route('support.requests.index'))->assertNotFound();
        $this->actingAs($michael)->get(route('support.requests.new'))->assertNotFound();
        $this->actingAs($michael)->get(route('support.requests.show', $supportRequest))->assertNotFound();
        $this->actingAs($michael)->get(route('admin.support.index'))->assertNotFound();
        $this->actingAs($michael)->get(route('admin.support.show', ['supportRequest' => $supportRequest->uuid]))->assertNotFound();

        $this->actingAs($michael)->post(route('support.requests.create'), [
            'category' => 'question',
            'subject' => 'Printer',
            'body' => 'The printer in the annex is on fire again.',
        ])->assertNotFound();

        $key = $michael->createToken('Support desk', ['*', 'administration'])->plainTextToken;

        $this->withToken($key)->json('GET', '/api/v1/support-requests')->assertNotFound();
        $this->withToken($key)->json('GET', '/api/v1/admin/support-requests')->assertNotFound();

        $this->assertSame(1, SupportRequest::query()->count());
    }
}
