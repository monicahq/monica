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

        $this->assertEquals(
            'Hi Michael, we’re glad you’re here.',
            new WelcomeViewModel(user: $user)->greeting(),
        );
    }
}
