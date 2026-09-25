<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MarkUserAsWelcomed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkUserAsWelcomedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_moment_somebody_was_welcomed(): void
    {
        $user = User::factory()->notWelcomed()->create([
            'first_name' => 'Pam',
        ]);

        $returned = new MarkUserAsWelcomed(
            user: $user,
        )->execute();

        $this->assertInstanceOf(User::class, $returned);
        $this->assertEqualsWithDelta(now()->timestamp, $user->refresh()->welcomed_at->timestamp, 5);
    }

    #[Test]
    public function it_keeps_the_first_moment_when_somebody_is_welcomed_again(): void
    {
        $firstVisit = now()->subMonth()->startOfSecond();

        $user = User::factory()->create([
            'first_name' => 'Jim',
            'welcomed_at' => $firstVisit,
        ]);

        new MarkUserAsWelcomed(
            user: $user,
        )->execute();

        $this->assertEquals($firstVisit, $user->refresh()->welcomed_at);
    }
}
