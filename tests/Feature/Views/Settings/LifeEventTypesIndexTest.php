<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Settings;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTypesIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_the_rail_entry_the_screen_is_showing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $url = route('settings.lifeEventTypes.category', $category);

        $onOne = $this->actingAs($user)->get($url)->getContent();
        $onEvery = $this->actingAs($user)->get(route('settings.lifeEventTypes.index'))->getContent();

        $this->assertStringContainsString('aria-current="page"', $this->anchorFor($url, $onOne));
        $this->assertStringNotContainsString('aria-current="page"', $this->anchorFor($url, $onEvery));
    }

    /**
     * The opening tag of the link to one address, so a test can read the
     * attributes it carries without describing where it sits.
     */
    private function anchorFor(string $url, string $html): string
    {
        preg_match('/<a\s[^>]*href="'.preg_quote($url, '/').'"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches, "No link to {$url}.");

        return $matches[0];
    }

    #[Test]
    public function it_offers_the_modal_that_adds_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.index'));

        $response->assertSee(route('settings.lifeEventCategories.create'), false);
    }

    #[Test]
    public function it_files_a_new_type_under_the_category_being_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('settings.lifeEventTypes.category', $category))
            ->assertSee(route('settings.lifeEventTypes.new', ['category' => $category->id]), false);
    }

    #[Test]
    public function it_says_why_a_category_holding_types_cannot_be_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.category', $category));

        $response->assertSee('aria-describedby="category-'.$category->id.'-no-delete"', false);
    }

    #[Test]
    public function it_carries_the_move_controls_of_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Work', 'position' => 2]);

        $response = $this->actingAs($user)->get(route('settings.lifeEventTypes.category', $first));

        $response->assertSee(route('settings.lifeEventCategoryPosition.update', $first), false);
    }
}
