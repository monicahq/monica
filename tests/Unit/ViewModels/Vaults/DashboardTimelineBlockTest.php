<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\DashboardTimelineBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTimelineBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $jim;

    private Contact $dwight;

    private LifeEventType $prank;

    private LifeEventType $promotion;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-22 10:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->jim = $this->contactNamed('Jim', 'Halpert');
        $this->dwight = $this->contactNamed('Dwight', 'Schrute');
        $work = LifeEventCategory::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Work']);
        $this->prank = LifeEventType::factory()->create(['account_id' => $this->user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Prank', 'position' => 1]);
        $this->promotion = LifeEventType::factory()->create(['account_id' => $this->user->account_id, 'life_event_category_id' => $work->id, 'name' => 'Promotion', 'position' => 2]);
    }

    #[Test]
    public function it_lists_what_is_coming_up_nearest_first_by_month_and_year(): void
    {
        $this->lifeEvent('2027-01-03', 'Art show', [$this->jim]);
        $this->lifeEvent('2026-12-18', 'Christmas party', [$this->jim]);
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);

        $block = $this->block(slice: 'upcoming');

        $this->assertSame('upcoming', $block->selectedSlice());
        $this->assertSame('Upcoming', $block->title());
        $this->assertSame('2 entries', $block->countLabel());
        $this->assertSame(['December 2026', 'January 2027'], array_column($block->months(), 'label'));
        $this->assertSame('Christmas party', $block->months()[0]['entries'][0]['title']);
    }

    #[Test]
    public function it_opens_on_the_past_newest_first_by_month_and_year_and_ignores_an_unknown_slice(): void
    {
        $this->lifeEvent('2026-12-18', 'Christmas party', [$this->jim]);
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);
        $this->lifeEvent('2023-02-01', 'Fun run', [$this->jim]);

        $block = $this->block();

        $this->assertSame('past', $block->selectedSlice());
        $this->assertSame('Past', $block->title());
        $this->assertSame('2 entries', $block->countLabel());
        $this->assertSame(['May 2025', 'February 2023'], array_column($block->months(), 'label'));
        $this->assertSame('past', $this->block(slice: '1990')->selectedSlice());
        $this->assertSame('2023', $this->block(slice: '2023')->selectedSlice());
    }

    #[Test]
    public function it_lists_a_year_newest_first_by_month_alone(): void
    {
        $this->lifeEvent('2025-03-01', 'Fire drill', [$this->dwight]);
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);

        $months = $this->block(slice: '2025')->months();

        $this->assertSame(['May', 'March'], array_column($months, 'label'));
    }

    #[Test]
    public function it_lists_each_slice_of_the_index_and_the_neighbours_of_the_selected_one(): void
    {
        $this->lifeEvent('2026-12-18', 'Christmas party', [$this->jim]);
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);
        $this->lifeEvent('2023-02-01', 'Fun run', [$this->jim]);

        $index = $this->block(slice: '2025')->index();

        $this->assertSame(['Upcoming', 'Past', '2025', '2023'], array_column($index, 'label'));
        $this->assertSame([false, false, true, false], array_column($index, 'isCurrent'));
        $this->assertSame(['upcoming', 'past', '2025', '2023'], array_column($index, 'slice'));
        $this->assertSame([false, false, true, true], array_column($index, 'isYear'));

        $this->assertSame('Newer: Upcoming', $this->block(slice: '2025')->newer()['label']);
        $this->assertSame('Older: 2023', $this->block(slice: '2025')->older()['label']);
        $this->assertNull($this->block(slice: 'upcoming')->newer());
        $this->assertSame('Older: 2025', $this->block(slice: 'upcoming')->older()['label']);
        $this->assertNull($this->block(slice: '2023')->older());
        $this->assertSame('Newer: Upcoming', $this->block(slice: 'past')->newer()['label']);
        $this->assertNull($this->block(slice: 'past')->older());
    }

    #[Test]
    public function it_places_a_life_event_known_only_to_the_year_last_and_writes_a_month_without_its_day(): void
    {
        $this->lifeEvent('2019-01-01', 'Moved to Scranton', [$this->jim], DatePrecisionEnum::Year);
        $this->lifeEvent('2019-06-01', 'Started at Dunder Mifflin', [$this->jim], DatePrecisionEnum::Month);

        $months = $this->block(slice: '2019')->months();

        $this->assertSame(['June', 'Sometime in 2019'], array_column($months, 'label'));
        $this->assertSame('June', $months[0]['entries'][0]['date']);
        $this->assertNull($months[0]['entries'][0]['relative']);
        $this->assertSame('2019', $months[1]['entries'][0]['date']);
    }

    #[Test]
    public function it_places_a_life_event_known_only_to_the_year_after_the_months_of_its_year_when_upcoming(): void
    {
        $this->lifeEvent('2028-02-01', 'Florida branch opens', [$this->jim]);
        $this->lifeEvent('2027-01-01', 'Move to Philadelphia', [$this->jim], DatePrecisionEnum::Year);
        $this->lifeEvent('2027-03-01', 'Wedding', [$this->jim]);

        $this->assertSame(['March 2027', 'Sometime in 2027', 'February 2028'], array_column($this->block(slice: 'upcoming')->months(), 'label'));
    }

    #[Test]
    public function it_places_a_life_event_known_only_to_the_year_after_the_months_of_its_year_in_the_past(): void
    {
        $this->lifeEvent('2018-02-01', 'Booze cruise', [$this->jim]);
        $this->lifeEvent('2019-01-01', 'Moved to Scranton', [$this->jim], DatePrecisionEnum::Year);
        $this->lifeEvent('2019-06-01', 'Started at Dunder Mifflin', [$this->jim]);

        $this->assertSame(['June 2019', 'Sometime in 2019', 'February 2018'], array_column($this->block()->months(), 'label'));
    }

    #[Test]
    public function it_continues_a_life_event_known_only_to_the_year_without_its_heading(): void
    {
        $rows = [];

        for ($index = 0; $index < 99; $index++) {
            $rows[] = ['title' => "Sale {$index}", 'happened_on' => '2019-05-01', 'date_precision' => DatePrecisionEnum::Day];
        }

        $rows[] = ['title' => 'Moved to Scranton', 'happened_on' => '2019-01-01', 'date_precision' => DatePrecisionEnum::Year];
        $rows[] = ['title' => 'Joined the softball team', 'happened_on' => '2019-01-01', 'date_precision' => DatePrecisionEnum::Year];

        LifeEvent::factory()->createMany(array_map(fn (array $row): array => [...$row, 'vault_id' => $this->vault->id, 'life_event_type_id' => $this->prank->id], $rows));

        $first = $this->block(slice: '2019')->months();
        $second = $this->block(slice: '2019', page: 2)->months();

        $this->assertSame(['May', 'Sometime in 2019'], array_column($first, 'label'));
        $this->assertSame(['Sometime in 2019'], array_column($second, 'label'));
        $this->assertFalse($second[0]['showsHeading']);
    }

    #[Test]
    public function it_says_how_far_a_day_is_within_eight_weeks(): void
    {
        $this->lifeEvent('2026-11-22', 'Today', [$this->jim]);
        $this->lifeEvent('2026-11-23', 'Tomorrow', [$this->jim]);
        $this->lifeEvent('2026-11-25', 'Soon', [$this->jim]);
        $this->lifeEvent('2027-01-17', 'Eight weeks', [$this->jim]);
        $this->lifeEvent('2027-01-18', 'Further', [$this->jim]);
        $this->lifeEvent('2026-11-21', 'Yesterday', [$this->jim]);

        $relative = collect($this->block(slice: 'upcoming')->months())->flatMap(fn (array $month): array => $month['entries'])->pluck('relative', 'title')->all();

        $this->assertSame('Today', $relative['Today']);
        $this->assertSame('Tomorrow', $relative['Tomorrow']);
        $this->assertSame('in 3 days', $relative['Soon']);
        $this->assertSame('in 8 weeks', $relative['Eight weeks']);
        $this->assertNull($relative['Further']);
        $this->assertSame('Yesterday', $this->block(slice: '2026')->months()[0]['entries'][0]['relative']);
    }

    #[Test]
    public function it_counts_the_days_from_the_readers_today_whatever_their_time_zone(): void
    {
        $this->lifeEvent('2026-11-25', 'Soon', [$this->jim]);
        $this->lifeEvent('2026-11-18', 'Recently', [$this->jim]);

        foreach (['America/Los_Angeles', 'Europe/Paris', 'Asia/Tokyo'] as $timezone) {
            $this->user->update(['timezone' => $timezone]);

            $this->assertSame('in 3 days', $this->block(slice: 'upcoming')->months()[0]['entries'][0]['relative'], $timezone);
            $this->assertSame('4 days ago', $this->block(slice: '2026')->months()[0]['entries'][0]['relative'], $timezone);
        }
    }

    #[Test]
    public function it_names_everybody_who_was_there_and_edits_through_the_first_full_contact(): void
    {
        $todd = Contact::factory()->partial()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'first_name' => 'Todd']);
        $lifeEvent = $this->lifeEvent('2025-05-01', 'Golf', [$todd, $this->jim]);
        $this->lifeEvent('2025-04-01', 'Nobody left', []);

        $entries = $this->block(slice: '2025')->months()[0]['entries'];

        $this->assertSame(['Todd', 'Jim Halpert'], array_column($entries[0]['with'], 'name'));
        $this->assertNull($entries[0]['with'][0]['url']);
        $this->assertSame(route('contacts.show', ['vault' => $this->vault, 'contact' => $this->jim]), $entries[0]['with'][1]['url']);
        $this->assertSame(route('contacts.lifeEvents.edit', ['vault' => $this->vault, 'contact' => $this->jim, 'lifeEvent' => $lifeEvent]), $entries[0]['editUrl']);
        $this->assertNull($this->block(slice: '2025')->months()[1]['entries'][0]['editUrl']);
    }

    #[Test]
    public function it_offers_the_details_of_every_entry_even_one_nobody_can_edit(): void
    {
        $golf = $this->lifeEvent('2025-05-01', 'Golf', [$this->jim]);
        $nobody = $this->lifeEvent('2025-04-01', 'Nobody left', []);

        $months = $this->block(slice: '2025')->months();

        $this->assertSame(route('vaults.lifeEvents.show', ['vault' => $this->vault, 'lifeEvent' => $golf]), $months[0]['entries'][0]['detailsUrl']);
        $this->assertSame("life-event-details-{$golf->uuid}", $months[0]['entries'][0]['detailsId']);
        $this->assertSame(route('vaults.lifeEvents.show', ['vault' => $this->vault, 'lifeEvent' => $nobody]), $months[1]['entries'][0]['detailsUrl']);
    }

    #[Test]
    public function it_lists_a_hundred_at_a_time_and_continues_a_month_without_its_heading(): void
    {
        $rows = [];

        for ($index = 0; $index < 101; $index++) {
            $rows[] = ['title' => "Sale {$index}", 'happened_on' => '2025-05-01'];
        }

        LifeEvent::factory()->createMany(array_map(fn (array $row): array => [...$row, 'vault_id' => $this->vault->id, 'life_event_type_id' => $this->prank->id], $rows));

        $first = $this->block(slice: '2025');
        $second = $this->block(slice: '2025', page: 2);

        $this->assertCount(100, $first->months()[0]['entries']);
        $this->assertSame(route('vaults.show', ['vault' => $this->vault, 'slice' => '2025', 'page' => 2]), $first->nextPageUrl());
        $this->assertCount(1, $second->months()[0]['entries']);
        $this->assertFalse($second->months()[0]['showsHeading']);
        $this->assertNull($second->nextPageUrl());
        $this->assertSame('101 entries', $second->countLabel());
    }

    #[Test]
    public function it_narrows_to_a_contact_and_a_type_and_keeps_them_in_every_address(): void
    {
        $this->lifeEvent('2025-05-01', 'Promoted', [$this->dwight], type: $this->promotion);
        $this->lifeEvent('2025-04-01', 'Jello stapler', [$this->dwight]);
        $this->lifeEvent('2025-03-01', 'Pam promoted', [$this->jim], type: $this->promotion);

        $block = $this->block(slice: '2025', contact: $this->dwight->uuid, type: $this->promotion->uuid);
        $entries = $block->months()[0]['entries'];

        $this->assertSame(['Promoted'], array_column($entries, 'title'));
        $this->assertTrue($block->isFiltered());
        $this->assertSame(['uuid' => $this->dwight->uuid, 'name' => 'Dwight Schrute'], $block->contactChoice());
        $this->assertSame(route('vaults.show', ['vault' => $this->vault, 'slice' => '2025']), $block->clearFiltersUrl());
        $this->assertSame([['category' => 'Work', 'types' => [
            ['uuid' => $this->prank->uuid, 'name' => 'Prank', 'isSelected' => false],
            ['uuid' => $this->promotion->uuid, 'name' => 'Promotion', 'isSelected' => true],
        ]]], $block->typeOptions());
    }

    #[Test]
    public function it_ignores_a_contact_or_a_type_it_cannot_find_in_the_vault(): void
    {
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);
        $stranger = Contact::factory()->create();

        $block = $this->block(contact: $stranger->uuid, type: LifeEventType::factory()->create()->uuid);

        $this->assertFalse($block->isFiltered());
        $this->assertSame('Dundies', $block->months()[0]['entries'][0]['title']);
    }

    #[Test]
    public function it_says_when_the_filters_empty_a_slice_and_when_the_vault_holds_nothing(): void
    {
        $this->assertTrue($this->block()->holdsNothing());

        $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);

        $block = $this->block(slice: '2025', contact: $this->dwight->uuid);

        $this->assertFalse($block->holdsNothing());
        $this->assertTrue($block->isEmptySlice());
        $this->assertSame(0, $block->index()[2]['count']);
    }

    #[Test]
    public function it_marks_the_life_event_just_added(): void
    {
        $lifeEvent = $this->lifeEvent('2025-05-01', 'Dundies', [$this->jim]);

        $block = new DashboardTimelineBlock(user: $this->user, vault: $this->vault, justAddedId: $lifeEvent->id);

        $this->assertTrue($block->months()[0]['entries'][0]['isJustAdded']);
    }

    #[Test]
    public function it_reads_the_database_as_often_however_much_the_vault_holds(): void
    {
        $this->lifeEvent('2025-05-01', 'Dundies', [$this->dwight], type: $this->promotion);
        $this->readsToDraw();
        $small = $this->readsToDraw();

        for ($year = 2006; $year <= 2026; $year++) {
            foreach (['Prank', 'Sale', 'Party'] as $title) {
                $this->lifeEvent("{$year}-05-01", "{$title} {$year}", [$this->dwight, $this->jim, $this->contactNamed($title, (string) $year)], type: $this->promotion);
            }
        }

        $large = $this->readsToDraw();

        $this->assertSame($small, $large);
        $this->assertLessThanOrEqual(6, $large);
    }

    private function readsToDraw(): int
    {
        $block = $this->block(slice: '2025', contact: $this->dwight->uuid, type: $this->promotion->uuid);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $block->total();
        $block->index();
        $block->title();
        $block->countLabel();
        $block->typeOptions();
        $block->contactChoice();
        $block->isFiltered();
        $block->isEmptySlice();
        $block->months();
        $block->nextPageUrl();
        $block->newer();
        $block->older();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function block(?string $slice = null, ?string $contact = null, ?string $type = null, int $page = 1): DashboardTimelineBlock
    {
        return new DashboardTimelineBlock(
            user: $this->user,
            vault: $this->vault,
            slice: $slice,
            contact: $contact,
            type: $type,
            page: $page,
        );
    }

    private function contactNamed(string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    /** @param list<Contact> $people */
    private function lifeEvent(string $happenedOn, string $title, array $people, DatePrecisionEnum $precision = DatePrecisionEnum::Day, ?LifeEventType $type = null): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $this->vault->id,
            'life_event_type_id' => ($type ?? $this->prank)->id,
            'happened_on' => $happenedOn,
            'date_precision' => $precision,
            'title' => $title,
        ]);

        foreach ($people as $position => $person) {
            LifeEventParticipant::factory()->create([
                'life_event_id' => $lifeEvent->id,
                'participant_id' => $person->id,
                'position' => $position,
            ]);
        }

        return $lifeEvent;
    }
}
