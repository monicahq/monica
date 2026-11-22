<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\LifeEventTimeline;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventTimelineTest extends TestCase
{
    use RefreshDatabase;

    private Vault $vault;

    private Contact $dwight;

    private Contact $jim;

    private LifeEventType $promotion;

    private LifeEventType $prank;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->vault = Vault::factory()->create();
        $this->dwight = Contact::factory()->create(['account_id' => $this->vault->account_id, 'vault_id' => $this->vault->id, 'first_name' => 'Dwight']);
        $this->jim = Contact::factory()->create(['account_id' => $this->vault->account_id, 'vault_id' => $this->vault->id, 'first_name' => 'Jim']);
        $work = LifeEventCategory::factory()->create(['account_id' => $this->vault->account_id, 'position' => 1]);
        $this->promotion = LifeEventType::factory()->create(['account_id' => $this->vault->account_id, 'life_event_category_id' => $work->id, 'name' => 'Promotion', 'position' => 2]);
        $this->prank = LifeEventType::factory()->create(['account_id' => $this->vault->account_id, 'life_event_category_id' => $work->id, 'name' => 'Prank', 'position' => 1]);
    }

    #[Test]
    public function it_counts_upcoming_first_then_every_year_newest_first(): void
    {
        $this->lifeEvent('2026-12-18', $this->promotion, [$this->dwight]);
        $this->lifeEvent('2027-01-03', $this->prank, [$this->jim]);
        $this->lifeEvent('2025-05-01', $this->prank, [$this->jim]);
        $this->lifeEvent('2023-02-01', $this->promotion, [$this->dwight]);

        $this->assertSame([
            ['slice' => 'upcoming', 'count' => 2, 'total' => 2],
            ['slice' => '2025', 'count' => 1, 'total' => 1],
            ['slice' => '2023', 'count' => 1, 'total' => 1],
        ], $this->timeline()->slices());
        $this->assertSame(4, $this->timeline()->total());
    }

    #[Test]
    public function it_counts_today_as_upcoming_and_the_rest_of_this_year_as_its_year(): void
    {
        $this->lifeEvent('2026-11-22', $this->prank, [$this->jim]);
        $this->lifeEvent('2026-11-21', $this->prank, [$this->jim]);
        $this->lifeEvent('2026-12-01', $this->prank, [$this->jim]);

        $this->assertSame([
            ['slice' => 'upcoming', 'count' => 2, 'total' => 2],
            ['slice' => '2026', 'count' => 1, 'total' => 1],
        ], $this->timeline()->slices());
    }

    #[Test]
    public function it_places_a_day_in_the_slice_the_counts_put_it_in_whatever_the_time_zone(): void
    {
        foreach (['America/Los_Angeles', 'UTC', 'Asia/Tokyo'] as $timezone) {
            $today = CarbonImmutable::parse('2026-11-22 12:00', $timezone)->startOfDay();

            $this->assertSame('upcoming', LifeEventTimeline::sliceOf(CarbonImmutable::parse('2026-11-22'), $today), $timezone);
            $this->assertSame('2026', LifeEventTimeline::sliceOf(CarbonImmutable::parse('2026-11-21'), $today), $timezone);
        }
    }

    #[Test]
    public function it_follows_the_filters_in_every_count_and_keeps_a_year_the_filters_empty(): void
    {
        $this->lifeEvent('2026-12-18', $this->promotion, [$this->dwight]);
        $this->lifeEvent('2025-05-01', $this->prank, [$this->jim, $this->dwight]);
        $this->lifeEvent('2025-06-01', $this->promotion, [$this->dwight]);
        $this->lifeEvent('2023-02-01', $this->promotion, [$this->jim]);

        $this->assertSame([
            ['slice' => 'upcoming', 'count' => 1, 'total' => 1],
            ['slice' => '2025', 'count' => 2, 'total' => 2],
            ['slice' => '2023', 'count' => 0, 'total' => 1],
        ], $this->timeline(contact: $this->dwight)->slices());

        $this->assertSame([
            ['slice' => 'upcoming', 'count' => 1, 'total' => 1],
            ['slice' => '2025', 'count' => 1, 'total' => 2],
            ['slice' => '2023', 'count' => 0, 'total' => 1],
        ], $this->timeline(type: $this->promotion, contact: $this->dwight)->slices());
    }

    #[Test]
    public function it_leaves_out_deleted_life_events_and_other_vaults(): void
    {
        $this->lifeEvent('2025-05-01', $this->prank, [$this->jim])->delete();
        LifeEvent::factory()->create(['happened_on' => '2024-05-01']);

        $this->assertSame([['slice' => 'upcoming', 'count' => 0, 'total' => 0]], $this->timeline()->slices());
        $this->assertSame(0, $this->timeline()->total());
    }

    #[Test]
    public function it_opens_on_upcoming_then_on_the_newest_year_that_matches(): void
    {
        $this->assertSame('upcoming', $this->timeline()->defaultSlice());

        $this->lifeEvent('2025-05-01', $this->prank, [$this->jim]);
        $this->lifeEvent('2023-02-01', $this->promotion, [$this->dwight]);

        $this->assertSame('2025', $this->timeline()->defaultSlice());
        $this->assertSame('2023', $this->timeline(contact: $this->dwight)->defaultSlice());

        $this->lifeEvent('2027-01-03', $this->prank, [$this->jim]);

        $this->assertSame('upcoming', $this->timeline()->defaultSlice());
    }

    #[Test]
    public function it_lists_upcoming_nearest_first_and_a_year_newest_first_up_to_today(): void
    {
        $christmas = $this->lifeEvent('2026-12-18', $this->prank, [$this->jim]);
        $artShow = $this->lifeEvent('2027-01-03', $this->prank, [$this->jim]);
        $earlier = $this->lifeEvent('2026-03-01', $this->prank, [$this->jim]);
        $later = $this->lifeEvent('2026-10-01', $this->prank, [$this->jim]);

        $this->assertSame([$christmas->id, $artShow->id], $this->timeline()->query('upcoming')->pluck('id')->all());
        $this->assertSame([$later->id, $earlier->id], $this->timeline()->query('2026')->pluck('id')->all());
    }

    #[Test]
    public function it_lists_only_what_matches_every_filter(): void
    {
        $match = $this->lifeEvent('2025-06-01', $this->promotion, [$this->dwight, $this->jim]);
        $this->lifeEvent('2025-05-01', $this->prank, [$this->dwight]);
        $this->lifeEvent('2025-04-01', $this->promotion, [$this->jim]);

        $this->assertSame([$match->id], $this->timeline(type: $this->promotion, contact: $this->dwight)->query('2025')->pluck('id')->all());
    }

    #[Test]
    public function it_lists_the_types_in_use_by_category_then_position_deleted_ones_included(): void
    {
        $this->lifeEvent('2025-06-01', $this->promotion, [$this->dwight]);
        $this->lifeEvent('2025-05-01', $this->prank, [$this->jim]);
        LifeEventType::factory()->create(['account_id' => $this->vault->account_id, 'name' => 'Unused']);
        $this->prank->delete();

        $this->assertSame(['Prank', 'Promotion'], LifeEventTimeline::typesIn($this->vault)->pluck('name')->all());
    }

    private function timeline(?LifeEventType $type = null, ?Contact $contact = null): LifeEventTimeline
    {
        return new LifeEventTimeline(
            vault: $this->vault,
            today: CarbonImmutable::create(2026, 11, 22),
            type: $type,
            contact: $contact,
        );
    }

    /** @param list<Contact> $people */
    private function lifeEvent(string $happenedOn, LifeEventType $type, array $people): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $this->vault->id,
            'life_event_type_id' => $type->id,
            'happened_on' => $happenedOn,
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
