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

class TemplatePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_template_and_renumbers_the_account_from_one(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = Template::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = Template::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $third = Template::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$third->uuid}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);

        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);
    }

    #[Test]
    public function it_puts_a_template_asked_for_a_place_past_the_end_last(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = Template::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        Template::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$first->uuid}/position", ['position' => 99])->assertOk();

        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_validates_the_position(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/position", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/position", ['position' => 2])->assertNotFound();
        $this->assertSame(1, $template->refresh()->position);
    }
}
