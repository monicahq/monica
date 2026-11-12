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

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }

    #[Test]
    public function it_offers_the_support_queue_only_while_support_is_switched_on(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->get(route('admin.accounts.index'))
            ->assertSee('href="'.route('admin.support.index').'"', escape: false);

        config(['monica.support_enabled' => false]);

        $this->actingAs($administrator)->get(route('admin.accounts.index'))
            ->assertDontSee('href="'.route('admin.support.index').'"', escape: false);
    }
}
