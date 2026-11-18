<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Support;

use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Support\SupportRequestFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_active_administrators_who_will_read_the_request(): void
    {
        foreach (['David' => true, 'Jan' => true, 'Robert' => false] as $firstName => $isActive) {
            $administrator = User::factory()->create(['first_name' => $firstName, 'is_active' => $isActive]);
            Administrator::factory()->create(['user_id' => $administrator->id]);
        }

        $viewModel = new SupportRequestFormViewModel(user: User::factory()->create());

        $this->assertEquals('David and Jan', $viewModel->administratorNames());
        $this->assertEquals('All 2 of them run this instance, and reply in person as soon as possible.', $viewModel->administratorLine());
    }

    #[Test]
    public function it_shows_the_portraits_of_the_two_people_who_read_the_requests(): void
    {
        $this->assertEquals(
            [asset('images/alexis.webp'), asset('images/regis.webp')],
            new SupportRequestFormViewModel(user: User::factory()->create())->portraitUrls(),
        );
    }

    #[Test]
    public function it_starts_on_the_category_picked_before_a_refused_send(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('billing', new SupportRequestFormViewModel(user: $user, selectedCategory: 'billing')->selectedCategory());
        $this->assertEquals('feature', new SupportRequestFormViewModel(user: $user, selectedCategory: 'scranton')->selectedCategory());
    }

    #[Test]
    public function it_gives_each_category_its_guidance(): void
    {
        $billing = new SupportRequestFormViewModel(user: User::factory()->create())->categories()[1];

        $this->assertEquals('billing', $billing['value']);
        $this->assertEquals('Let’s sort out your billing.', $billing['heading']);
        $this->assertCount(2, $billing['paragraphs']);
    }
}
