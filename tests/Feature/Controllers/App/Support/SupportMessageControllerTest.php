<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Support;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_reply_and_comes_back_to_the_request(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('support.requests.messages.create', $supportRequest), [
            'body' => 'Settings show New York. I moved to Paris in March.',
        ]);

        $response->assertRedirect(route('support.requests.show', $supportRequest));
        $response->assertSessionHas('status', 'Your reply is sent.');
        $this->assertDatabaseHas('support_messages', [
            'support_request_id' => $supportRequest->id,
            'body' => 'Settings show New York. I moved to Paris in March.',
        ]);
    }

    #[Test]
    public function it_refuses_an_empty_reply(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('support.requests.messages.create', $supportRequest), ['body' => ''])
            ->assertSessionHasErrors(['body']);
    }

    #[Test]
    public function it_refuses_a_sixth_reply_within_a_minute(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)->post(route('support.requests.messages.create', $supportRequest), ['body' => "Bears, beets, Battlestar Galactica {$attempt}."])
                ->assertRedirect();
        }

        $this->actingAs($user)->post(route('support.requests.messages.create', $supportRequest), ['body' => 'Identity theft is not a joke.'])
            ->assertTooManyRequests();
    }
}
