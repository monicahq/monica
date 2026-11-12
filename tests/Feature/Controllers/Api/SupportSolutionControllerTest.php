<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportSolutionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_request_as_solved(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/support-requests/{$supportRequest->uuid}/solution")
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'solved');
    }

    #[Test]
    public function it_refuses_a_request_already_solved(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/support-requests/{$supportRequest->uuid}/solution")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
