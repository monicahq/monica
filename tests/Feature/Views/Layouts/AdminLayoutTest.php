<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_which_of_the_two_products_you_are_looking_at(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee('Admin space')
            ->assertSee('Instance')
            ->assertSee('You are looking at the instance, not at your own account.')
            ->assertSee('Anything you change here applies to every account on it.');
    }

    #[Test]
    public function it_marks_the_one_section_you_are_in(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.accounts.index'));

        $response->assertSee('aria-label="Admin sections"', escape: false);
        $response->assertSeeInOrder(['href="'.route('admin.accounts.index').'"', 'aria-current="page"'], escape: false);
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
    }

    #[Test]
    public function it_marks_the_section_a_page_inside_it_belongs_to(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get(route('admin.accounts.show', $michael->account_id));

        $sections = Str::betweenFirst($response->getContent(), 'aria-label="Admin sections"', '</nav>');

        $this->assertStringContainsString('aria-current="true"', $sections);
        $this->assertStringNotContainsString('aria-current="page"', $sections);
    }

    #[Test]
    public function it_offers_no_way_into_a_vault(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertDontSee('/vaults', escape: false);
    }

    #[Test]
    public function it_offers_a_way_back_to_the_ordinary_application(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee('Leave the admin space')
            ->assertSee('aria-label="Leave the admin space"', escape: false);
    }

    #[Test]
    public function it_offers_a_way_out(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee(route('auth.login.destroy'), escape: false)
            ->assertSee('Sign out');
    }

    #[Test]
    public function it_carries_no_dock_since_the_dock_points_into_a_vault(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertDontSee('x-dock', escape: false)
            ->assertDontSee('aria-label="Vaults"', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
