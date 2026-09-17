<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\ActivityTypes;

use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use App\ViewModels\Settings\Account\ActivityTypes\ActivityTypesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityTypesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_categories_in_position_order_with_their_counts(): void
    {
        $user = User::factory()->create();
        $second = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Work', 'position' => 2]);
        $first = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        ActivityType::factory()->count(2)->create(['account_id' => $user->account_id, 'activity_category_id' => $first->id]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $second->id]);

        $rail = new ActivityTypesViewModel(user: $user)->rail();

        $this->assertSame(['Family', 'Work'], array_column($rail, 'name'));
        $this->assertSame([2, 1], array_column($rail, 'count'));
        $this->assertTrue($rail[0]['isFirst']);
        $this->assertTrue($rail[1]['isLast']);
    }

    #[Test]
    public function it_shows_every_type_grouped_by_category_then_by_its_own_order(): void
    {
        $user = User::factory()->create();
        $first = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $second->id, 'name' => 'Meeting', 'position' => 1]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $first->id, 'name' => 'Visit', 'position' => 2]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $first->id, 'name' => 'Call', 'position' => 1]);

        $viewModel = new ActivityTypesViewModel(user: $user);

        $this->assertTrue($viewModel->showsEveryType());
        $this->assertSame(['Call', 'Visit', 'Meeting'], array_column($viewModel->rows(), 'name'));
        $this->assertSame(3, $viewModel->totalCount());
    }

    #[Test]
    public function it_shows_only_the_types_of_the_selected_category(): void
    {
        $user = User::factory()->create();
        $selected = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Health']);
        $other = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $selected->id, 'name' => 'Therapy']);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $other->id, 'name' => 'Interview']);

        $viewModel = new ActivityTypesViewModel(user: $user, selected: $selected);

        $this->assertFalse($viewModel->showsEveryType());
        $this->assertSame('Health', $viewModel->panelTitle());
        $this->assertSame(['Therapy'], array_column($viewModel->rows(), 'name'));
    }

    #[Test]
    public function it_names_the_category_of_a_row_only_when_every_type_is_shown(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Travel']);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        $this->assertSame('Travel', new ActivityTypesViewModel(user: $user)->rows()[0]['category']);
        $this->assertNull(new ActivityTypesViewModel(user: $user, selected: $category)->rows()[0]['category']);
    }

    #[Test]
    public function it_describes_the_selected_category_and_counts_only_the_offered_types(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);
        ActivityType::factory()->inactive()->create(['account_id' => $user->account_id, 'activity_category_id' => $category->id]);

        $selected = new ActivityTypesViewModel(user: $user, selected: $category)->selectedCategory();

        $this->assertSame(1, $selected['activeTypeCount']);
        $this->assertFalse($selected['isEmpty']);
    }

    #[Test]
    public function it_marks_a_category_that_is_switched_off(): void
    {
        $user = User::factory()->create();
        $category = ActivityCategory::factory()->inactive()->create(['account_id' => $user->account_id]);

        $viewModel = new ActivityTypesViewModel(user: $user, selected: $category);

        $this->assertTrue($viewModel->selectedIsInactive());
        $this->assertFalse($viewModel->rail()[0]['isActive']);
    }

    #[Test]
    public function it_shows_nothing_of_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);
        ActivityType::factory()->create(['account_id' => $dwight->account_id]);

        $viewModel = new ActivityTypesViewModel(user: $michael);

        $this->assertSame([], $viewModel->rail());
        $this->assertSame([], $viewModel->rows());
        $this->assertSame(0, $viewModel->totalCount());
    }
}
