<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Account;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_template_being_offered(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_active', false);
        $this->assertFalse($template->refresh()->is_active);
    }

    #[Test]
    public function it_offers_a_template_again(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->inactive()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', true)
            ->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it_when_sent_twice(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => false])->assertOk();
        $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_active', false);
    }

    #[Test]
    public function it_validates_that_a_state_is_sent(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);
    }

    #[Test]
    public function it_returns_not_found_for_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => false])->assertNotFound();
        $this->assertTrue($template->refresh()->is_active);
    }
}
