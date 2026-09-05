<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestScreenTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function guestScreens(): array
    {
        return [
            'sign in' => ['auth.login.new'],
            'sign up' => ['auth.register.new'],
            'forgotten password' => ['auth.password.new'],
            'magic link' => ['auth.magicLink.new'],
        ];
    }

    #[Test]
    #[DataProvider('guestScreens')]
    public function it_holds_what_it_asks_for_in_the_middle_of_the_screen(string $route): void
    {
        $this->get(route($route))
            ->assertStatus(200)
            ->assertSee('flex min-w-0 flex-col justify-center', escape: false)
            ->assertSee('mx-auto my-auto w-full max-w-md', escape: false);
    }
}
