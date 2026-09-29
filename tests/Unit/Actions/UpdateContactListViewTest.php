<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactListView;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactListViewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_remembers_how_somebody_was_looking_at_the_people(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $returned = new UpdateContactListView(
            user: $user,
            grouping: ContactGroupingEnum::Favourites,
            sort: ContactSortEnum::LastSpoke,
            direction: SortDirectionEnum::Descending,
        )->execute();

        $this->assertInstanceOf(User::class, $returned);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'contact_list_grouping' => 'favourites',
            'contact_list_sort' => 'last-spoke',
            'contact_list_direction' => 'desc',
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_writes_nothing_when_the_three_choices_already_match(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'names-only',
            'contact_list_sort' => 'added',
            'contact_list_direction' => 'asc',
        ]);

        $savedAt = $user->updated_at;

        $this->travelTo(now()->addMinute());

        new UpdateContactListView(
            user: $user,
            grouping: ContactGroupingEnum::NamesOnly,
            sort: ContactSortEnum::Added,
            direction: SortDirectionEnum::Ascending,
        )->execute();

        $this->assertEquals($savedAt, $user->refresh()->updated_at);
    }

    #[Test]
    public function it_writes_when_one_of_the_three_choices_differs(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'names-only',
            'contact_list_sort' => 'added',
            'contact_list_direction' => 'asc',
        ]);

        new UpdateContactListView(
            user: $user,
            grouping: ContactGroupingEnum::NamesOnly,
            sort: ContactSortEnum::Added,
            direction: SortDirectionEnum::Descending,
        )->execute();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'contact_list_grouping' => 'names-only',
            'contact_list_direction' => 'desc',
        ]);
    }

    #[Test]
    public function it_remembers_nothing_for_anybody_else(): void
    {
        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();

        new UpdateContactListView(
            user: $user,
            grouping: ContactGroupingEnum::NamesOnly,
            sort: ContactSortEnum::Nickname,
            direction: SortDirectionEnum::Ascending,
        )->execute();

        $this->assertNull($somebodyElse->refresh()->contact_list_grouping);
    }
}
