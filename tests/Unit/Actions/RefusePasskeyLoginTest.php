<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RefusePasskeyLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefusePasskeyLoginTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'These credentials do not match our records.';

    #[Test]
    public function it_lets_an_active_user_through(): void
    {
        $user = User::factory()->create();

        $result = new RefusePasskeyLogin(candidate: $user)->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function it_refuses_a_credential_nobody_owns(): void
    {
        $this->expectException(ValidationException::class);

        new RefusePasskeyLogin(candidate: null)->execute();
    }

    #[Test]
    public function it_refuses_a_deactivated_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->expectException(ValidationException::class);

        new RefusePasskeyLogin(candidate: $user)->execute();
    }

    #[Test]
    public function it_allows_a_user_who_also_signs_in_through_a_provider(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertTrue(new RefusePasskeyLogin(candidate: $user)->execute()->is($user));
    }

    #[Test]
    public function it_reports_every_refusal_with_the_same_message(): void
    {
        $messages = [];

        foreach ([null, User::factory()->create(['is_active' => false])] as $candidate) {
            try {
                new RefusePasskeyLogin(candidate: $candidate)->execute();
            } catch (ValidationException $exception) {
                $messages[] = $exception->errors();
            }
        }

        $this->assertCount(2, $messages);
        $this->assertEquals([['passkey' => [self::MESSAGE]]], array_unique($messages, SORT_REGULAR));
    }
}
