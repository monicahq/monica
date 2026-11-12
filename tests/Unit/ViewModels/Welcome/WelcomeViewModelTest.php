<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Welcome;

use App\Models\User;
use App\ViewModels\Welcome\WelcomeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_greets_somebody_by_the_first_word_of_their_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael Gary', 'last_name' => 'Scott']);

        $greeting = new WelcomeViewModel(user: $user)->greeting();

        $this->assertStringContainsString('Michael', $greeting);
        $this->assertStringNotContainsString('Gary', $greeting);
    }
}
