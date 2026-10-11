<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateLifeEvent;
use App\Enums\DateFormatEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\VaultColourEnum;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactTimelineBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTimelineBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_years_newest_first_and_the_dates_newest_first_within_one(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $this->lifeEventWith($jim, '2025-05-21', 'Engagement at the gas station');
        $this->lifeEventWith($jim, '2026-03-02', 'Weekend in Philadelphia');
        $this->lifeEventWith($jim, '2026-07-03', 'Moved to a house near the office');

        $years = $this->block($user, $vault, $jim)->years();

        $this->assertEquals([2026, 2025], array_column($years, 'year'));
        $this->assertEquals(['Moved to a house near the office', 'Weekend in Philadelphia'], array_column($years[0]['entries'], 'title'));
        $this->assertEquals('2 entries', $years[0]['countLabel']);
        $this->assertEquals('1 entry', $years[1]['countLabel']);
    }

    #[Test]
    public function it_counts_the_last_day_of_the_year_in_its_year(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $this->lifeEventWith($jim, '2025-12-31', 'New year’s eve');
        $this->lifeEventWith($jim, '2025-06-01', 'Summer');

        $this->assertEquals('2 entries', $this->block($user, $vault, $jim)->years()[0]['countLabel']);
    }

    #[Test]
    public function it_lists_the_latest_recorded_first_within_one_day(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $this->lifeEventWith($jim, '2026-07-03', 'Lunch with Pam');
        $this->lifeEventWith($jim, '2026-07-03', 'Prank on Dwight');

        $entries = $this->block($user, $vault, $jim)->years()[0]['entries'];

        $this->assertEquals(['Prank on Dwight', 'Lunch with Pam'], array_column($entries, 'title'));
    }

    #[Test]
    public function it_leaves_out_a_life_event_the_contact_did_not_take_part_in(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $this->lifeEventWith($dwight, '2026-04-01', 'Dwight told everybody about Jim');

        $block = $this->block($user, $vault, $jim);

        $this->assertEquals([], $block->years());
        $this->assertTrue($block->holdsNothing());
    }

    #[Test]
    public function it_counts_the_whole_year_beyond_the_page(): void
    {
        [$user, $vault, $jim] = $this->jim();

        for ($day = 1; $day <= ContactTimelineBlock::PER_PAGE + 2; $day++) {
            $this->lifeEventWith($jim, now()->setDate(2026, 1, 1)->addDays($day)->toDateString(), "Prank {$day}");
        }

        $block = $this->block($user, $vault, $jim);

        $this->assertEquals('32 entries', $block->years()[0]['countLabel']);
        $this->assertCount(ContactTimelineBlock::PER_PAGE, $block->years()[0]['entries']);
        $this->assertNotNull($block->nextPageUrl());
    }

    #[Test]
    public function it_does_not_repeat_the_heading_of_a_year_the_previous_page_ended_in(): void
    {
        [$user, $vault, $jim] = $this->jim();

        for ($day = 1; $day <= ContactTimelineBlock::PER_PAGE + 2; $day++) {
            $this->lifeEventWith($jim, now()->setDate(2026, 1, 1)->addDays($day)->toDateString(), "Prank {$day}");
        }

        $this->lifeEventWith($jim, '2025-12-25', 'Christmas party');

        $years = $this->block($user, $vault, $jim, page: 2)->years();

        $this->assertFalse($years[0]['showsHeading']);
        $this->assertEquals(2026, $years[0]['year']);
        $this->assertTrue($years[1]['showsHeading']);
    }

    #[Test]
    public function it_names_a_life_event_without_a_title_after_its_type(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->lifeEventWith($jim, '2026-06-01', null);
        $lifeEvent->lifeEventType->update(['name' => 'Booze cruise', 'is_active' => false]);

        $this->assertEquals('Booze cruise', $this->block($user, $vault, $jim)->years()[0]['entries'][0]['title']);
    }

    #[Test]
    public function it_marks_a_life_event_with_the_glyph_and_colour_of_its_type(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $this->lifeEventWith($jim, '2026-06-01', 'Dundies')->lifeEventType->update(['icon' => 'star', 'colour' => VaultColourEnum::Plum->value]);
        $this->lifeEventWith($jim, '2026-05-01', 'Fun run')->lifeEventType->update(['icon' => null, 'colour' => null]);

        $entries = $this->block($user, $vault, $jim)->years()[0]['entries'];

        $this->assertSame(['star', Section::DEFAULT_ICON], array_column($entries, 'icon'));
        $this->assertSame([VaultColourEnum::Plum->tint(), VaultColourEnum::neutralTint()], array_column($entries, 'tint'));
    }

    #[Test]
    public function it_names_the_other_people_who_were_there_with_a_way_to_their_page(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->lifeEventWith($jim, '2026-06-01', 'Beach day');
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam']);
        $cece = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Cece', 'is_partial' => true]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $pam->id, 'position' => 1]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $cece->id, 'position' => 2]);

        $this->assertEquals([
            ['name' => 'Pam', 'url' => route('contacts.show', ['vault' => $vault, 'contact' => $pam])],
            ['name' => 'Cece', 'url' => null],
        ], $this->block($user, $vault, $jim)->years()[0]['entries'][0]['with']);
    }

    #[Test]
    public function it_lists_everything_else_written_about_the_life_event(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $call = $this->lifeEventTypeAsking($user, 'Phone call', ['Channel' => FieldTypeEnum::Text, 'Mood' => FieldTypeEnum::Text]);
        [$channel] = $call->structure->publishedVersion->inputFields()->all();

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $call,
            happenedOn: CarbonImmutable::parse('2026-08-30'),
            narrative: 'Mostly about the move.',
            location: 'Scranton',
            durationInMinutes: 40,
            values: [$channel->id => 'Mobile'],
        )->execute();

        $entry = $this->block($user, $vault, $jim)->years()[0]['entries'][0];

        $this->assertEquals(['40 minutes', 'Scranton', 'Channel: Mobile'], $entry['details']);
        $this->assertEquals('Mostly about the move.', $entry['narrative']);
    }

    #[Test]
    public function it_marks_the_life_event_that_was_just_added(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $this->lifeEventWith($jim, '2026-01-01', 'Older');
        $added = $this->lifeEventWith($jim, '2026-02-01', 'Added');

        $entries = $this->block($user, $vault, $jim, justAddedId: $added->id)->years()[0]['entries'];

        $this->assertTrue($entries[0]['isJustAdded']);
        $this->assertFalse($entries[1]['isJustAdded']);
    }

    #[Test]
    public function it_marks_the_life_event_being_corrected_and_the_one_just_corrected(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $older = $this->lifeEventWith($jim, '2026-01-01', 'Older');
        $newer = $this->lifeEventWith($jim, '2026-02-01', 'Newer');

        $entries = (new ContactTimelineBlock(user: $user, vault: $vault, contact: $jim, editingId: $newer->id, editedId: $older->id))->years()[0]['entries'];

        $this->assertTrue($entries[0]['isEditing']);
        $this->assertFalse($entries[0]['isEdited']);
        $this->assertTrue($entries[1]['isEdited']);
        $this->assertEquals(route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $newer]), $entries[0]['editUrl']);
    }

    #[Test]
    public function it_knows_which_life_events_this_page_draws(): void
    {
        [$user, $vault, $jim] = $this->jim();

        for ($day = 1; $day <= ContactTimelineBlock::PER_PAGE; $day++) {
            $this->lifeEventWith($jim, now()->setDate(2026, 3, 1)->addDays($day)->toDateString(), "Prank {$day}");
        }

        $older = $this->lifeEventWith($jim, '2025-01-01', 'Older');
        $block = $this->block($user, $vault, $jim);

        $this->assertFalse($block->lists($older->id));
        $this->assertTrue($block->lists((int) $block->years()[0]['entries'][0]['id']));
    }

    #[Test]
    public function it_says_whose_timeline_a_deletion_takes_the_life_event_off(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->lifeEventWith($jim, '2026-06-01', 'Beach day');
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam']);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $pam->id, 'position' => 1]);

        $this->assertEquals(
            'It is removed from the timeline of Jim and Pam. The people stay.',
            $this->block($user, $vault, $jim)->years()[0]['entries'][0]['deleteLine'],
        );
    }

    #[Test]
    public function it_writes_the_day_in_the_date_format_the_reader_chose(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $user->update(['date_format' => DateFormatEnum::YearFirst]);
        $this->lifeEventWith($jim, '2026-07-03', 'Moved');

        $this->assertEquals('07-03', $this->block($user, $vault, $jim)->years()[0]['entries'][0]['date']);
    }

    #[Test]
    public function it_links_every_entry_to_the_contact_page_when_read_only(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->lifeEventWith($jim, '2026-07-03', 'Moved to a house near the office');

        $block = new ContactTimelineBlock(user: $user, vault: $vault, contact: $jim, isReadOnly: true);

        $this->assertTrue($block->isReadOnly());
        $this->assertEquals(
            route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]),
            $block->years()[0]['entries'][0]['editUrl'],
        );
    }

    #[Test]
    public function it_lists_as_many_entries_as_asked_and_no_further_page_when_read_only(): void
    {
        [$user, $vault, $jim] = $this->jim();

        for ($day = 1; $day <= 12; $day++) {
            $this->lifeEventWith($jim, sprintf('2026-07-%02d', $day), "Day {$day}");
        }

        $block = new ContactTimelineBlock(user: $user, vault: $vault, contact: $jim, perPage: 10, isReadOnly: true);

        $this->assertCount(10, $block->years()[0]['entries']);
        $this->assertSame('Day 12', $block->years()[0]['entries'][0]['title']);
        $this->assertNull($block->nextPageUrl());
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function jim(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        return [$user, $vault, $jim];
    }

    private function lifeEventWith(Contact $contact, string $happenedOn, ?string $title): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $contact->vault_id,
            'happened_on' => $happenedOn,
            'title' => $title,
        ]);

        LifeEventParticipant::factory()->create([
            'life_event_id' => $lifeEvent->id,
            'participant_id' => $contact->id,
        ]);

        return $lifeEvent;
    }

    private function block(User $user, Vault $vault, Contact $contact, int $page = 1, ?int $justAddedId = null): ContactTimelineBlock
    {
        return new ContactTimelineBlock(
            user: $user,
            vault: $vault,
            contact: $contact,
            page: $page,
            justAddedId: $justAddedId,
        );
    }
}
