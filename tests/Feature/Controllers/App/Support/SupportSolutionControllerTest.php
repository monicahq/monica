<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Support;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportSolutionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_the_request_as_solved(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('support.requests.solution.create', $supportRequest));

        $response->assertRedirect(route('support.requests.show', $supportRequest));
        $response->assertSessionHas('status', 'Marked as solved.');
        $this->assertEquals('solved', $supportRequest->refresh()->status->value);
    }

    #[Test]
    public function it_says_so_when_the_request_is_already_solved(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('support.requests.solution.create', $supportRequest))
            ->assertSessionHas('error', 'This request is already solved.');
    }
}
