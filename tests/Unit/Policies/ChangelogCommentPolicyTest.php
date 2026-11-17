<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogCommentPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_to_hide_a_public_comment(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('hide', ChangelogComment::factory()->create()));
    }

    #[Test]
    public function it_refuses_to_hide_a_private_comment(): void
    {
        $response = Gate::forUser(User::factory()->create())->inspect('hide', ChangelogComment::factory()->private()->create());

        $this->assertTrue($response->denied());
        $this->assertSame('A private comment is shown to nobody, so there is nothing to hide.', $response->message());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('hide', ChangelogComment::factory()->create()));
    }
}
