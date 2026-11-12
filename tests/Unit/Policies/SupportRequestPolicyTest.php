<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_solve_on_a_request_still_open(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('solve', SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id])));
    }

    #[Test]
    public function it_refuses_solve_on_a_request_already_solved(): void
    {
        $user = User::factory()->create();

        $response = Gate::forUser($user)->inspect('solve', SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]));

        $this->assertTrue($response->denied());
        $this->assertSame('This request is already solved.', $response->message());
    }

    #[Test]
    public function it_refuses_solve_when_nobody_is_signed_in(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('solve', SupportRequest::factory()->create()));
    }
}
