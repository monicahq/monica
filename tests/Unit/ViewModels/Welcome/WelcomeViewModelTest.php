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

    #[Test]
    public function it_sends_the_button_to_the_address_that_marks_somebody_as_welcomed(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(
            route('welcome.update'),
            new WelcomeViewModel(user: $user)->dismissUrl(),
        );
    }

    #[Test]
    public function it_shows_the_portraits_of_the_two_people_who_wrote_the_letter(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(
            [asset('images/regis.webp'), asset('images/alexis.webp')],
            new WelcomeViewModel(user: $user)->portraitUrls(),
        );
    }
}
