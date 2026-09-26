<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Enums\AddressLabelEnum;
use App\Enums\ContactCategoryEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\RelationshipCategoryEnum;
use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\Carbon;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_monica_in_as_the_only_user_and_the_me_contact_of_the_vault(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertCount(1, User::all());
        $this->assertDatabaseHas('users', [
            'first_name' => 'Monica',
            'last_name' => 'Geller',
            'email' => ExampleAccountSeeder::EMAIL,
        ]);
        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);

        $vault = Vault::query()->firstOrFail();
        $me = Contact::query()->where('vault_id', $vault->id)->where('is_me', true)->firstOrFail();

        $this->assertEquals('Monica', $me->first_name);
        $this->assertEquals('Geller', $me->last_name);
        $this->assertCount(1, Contact::query()->where('is_me', true)->get());
    }

    #[Test]
    public function it_fills_the_vault_with_the_rest_of_the_cast_as_the_show_gives_them(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertGreaterThanOrEqual(15, Contact::query()->count());

        foreach (['Rachel', 'Ross', 'Chandler', 'Joey', 'Phoebe'] as $firstName) {
            $this->assertDatabaseHas('contacts', ['first_name' => $firstName]);
        }

        $joey = Contact::query()->where('first_name', 'Joey')->firstOrFail();

        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $joey->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
            'value_date' => '1968-01-09 00:00:00',
        ]);
        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $joey->id,
            'field_key' => DefaultFieldEnum::Phone->value,
            'value_string' => '+1 212 555 0109',
        ]);
        $this->assertStringContainsString(
            'Drake Ramoray',
            (string) FieldValue::query()
                ->where('valuable_id', $joey->id)
                ->where('field_key', DefaultFieldEnum::Note->value)
                ->value('value_text'),
        );

        $gunther = Contact::query()->where('first_name', 'Gunther')->firstOrFail();
        $home = FieldValue::query()->where('valuable_id', $joey->id)->where('field_key', DefaultFieldEnum::Address->value)->sole();
        $work = FieldValue::query()->where('valuable_id', $gunther->id)->where('field_key', DefaultFieldEnum::Address->value)->sole();

        $this->assertEquals([
            'po_box' => '',
            'extended' => '',
            'street' => '90 Bedford Street, apartment 19',
            'city' => 'New York',
            'region' => '',
            'postal_code' => 'NY 10014',
            'country' => 'United States',
        ], $home->value_json);
        $this->assertSame(AddressLabelEnum::Home->value, $home->valueListItem->key);
        $this->assertSame(AddressLabelEnum::Work->value, $work->valueListItem->key);
        $ross = Contact::query()->where('first_name', 'Ross')->firstOrFail();
        $category = FieldValue::query()
            ->where('valuable_id', $ross->id)
            ->where('field_key', DefaultFieldEnum::Category->value)
            ->firstOrFail();
        $family = ValueListItem::query()->where('key', ContactCategoryEnum::Family->value)->firstOrFail();

        $this->assertEquals([$family->id], $category->value_list_item_ids);

        $this->assertDatabaseMissing('field_values', [
            'valuable_id' => $gunther->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
        ]);
        $this->assertFalse(Reminder::query()->where('remindable_id', $gunther->id)->exists());

        $favourites = Contact::query()->where('is_favourite', true)->pluck('first_name');

        $this->assertCount(3, $favourites);
        $this->assertContains('Rachel', $favourites);
        $this->assertContains('Ross', $favourites);
        $this->assertContains('Chandler', $favourites);

        $this->assertTrue(Contact::query()->where('last_name', 'Heckles')->firstOrFail()->is_archived);
    }

    #[Test]
    public function it_sets_a_different_reminder_on_every_birthday_but_monicas_own(): void
    {
        new ExampleAccountSeeder()->run();

        $birthdays = FieldValue::query()->where('field_key', DefaultFieldEnum::Birthday->value)->count();
        $monica = Contact::query()->where('is_me', true)->firstOrFail();
        $reminders = Reminder::query()->get();

        $this->assertEquals($birthdays - 1, $reminders->count());
        $this->assertGreaterThanOrEqual(5, $reminders->count());
        $this->assertEquals($reminders->count(), $reminders->where('is_active', true)->count());
        $this->assertFalse(Reminder::query()->where('remindable_id', $monica->id)->exists());
        $this->assertEquals($reminders->count(), $reminders->pluck('name')->unique()->count());
        $this->assertGreaterThan(1, $reminders->pluck('moments')->map(fn (array $moments): string => json_encode($moments))->unique()->count());
    }

    #[Test]
    #[DataProvider('monthsToSeedIn')]
    public function it_has_something_due_whatever_month_it_is_seeded_in(string $today): void
    {
        Carbon::setTestNow(Carbon::parse($today));

        new ExampleAccountSeeder()->run();

        $this->assertTrue(ReminderOccurrence::query()->exists());
    }

    /** @return array<string, array<int, string>> */
    public static function monthsToSeedIn(): array
    {
        return [
            'late spring' => ['2027-05-20'],
            'early summer' => ['2027-06-15'],
            'high summer' => ['2027-07-15'],
            'late summer' => ['2027-08-15'],
            'early autumn' => ['2027-09-15'],
            'winter' => ['2027-12-01'],
        ];
    }

    #[Test]
    public function it_seeds_with_nothing_working_the_queue(): void
    {
        config(['queue.default' => 'database']);

        new ExampleAccountSeeder()->run();

        $this->assertGreaterThanOrEqual(15, Contact::query()->count());
        $this->assertDatabaseCount('jobs', 0);
    }

    #[Test]
    public function it_can_be_run_again_without_building_a_second_account(): void
    {
        new ExampleAccountSeeder()->run();

        $contacts = Contact::query()->count();

        new ExampleAccountSeeder()->run();

        $this->assertCount(1, User::all());
        $this->assertEquals($contacts, Contact::query()->count());
    }

    #[Test]
    public function it_links_every_person_to_somebody_as_the_show_does(): void
    {
        new ExampleAccountSeeder()->run();

        $linked = Relationship::query()->pluck('contact_a_id')->merge(Relationship::query()->pluck('contact_b_id'))->unique();

        $this->assertEquals(25, Contact::query()->count());
        $this->assertCount(0, Contact::query()->whereNotIn('id', $linked)->pluck('first_name'));

        $ross = Contact::query()->where('first_name', 'Ross')->firstOrFail();
        $ben = Contact::query()->where('first_name', 'Ben')->firstOrFail();
        $monica = Contact::query()->where('is_me', true)->firstOrFail();
        $chandler = Contact::query()->where('first_name', 'Chandler')->firstOrFail();

        $this->assertDatabaseHas('relationships', [
            'relationship_type_id' => RelationshipType::query()->where('key', RelationshipTypeEnum::ParentChild->value)->value('id'),
            'contact_a_id' => $ross->id,
            'contact_b_id' => $ben->id,
        ]);
        $this->assertDatabaseHas('relationships', [
            'relationship_type_id' => RelationshipType::query()->where('key', RelationshipTypeEnum::Spouse->value)->value('id'),
            'contact_a_id' => min($monica->id, $chandler->id),
            'contact_b_id' => max($monica->id, $chandler->id),
            'status' => RelationshipStatusEnum::Current->value,
            'started_on' => '2001-05-17 00:00:00',
        ]);
    }

    #[Test]
    public function it_marks_the_relationships_the_show_ended_as_ended(): void
    {
        new ExampleAccountSeeder()->run();

        $ross = Contact::query()->where('first_name', 'Ross')->firstOrFail();
        $carol = Contact::query()->where('first_name', 'Carol')->firstOrFail();
        $joey = Contact::query()->where('first_name', 'Joey')->firstOrFail();
        $rachel = Contact::query()->where('first_name', 'Rachel')->firstOrFail();

        $this->assertDatabaseHas('relationships', [
            'relationship_type_id' => RelationshipType::query()->where('key', RelationshipTypeEnum::Spouse->value)->value('id'),
            'contact_a_id' => min($ross->id, $carol->id),
            'contact_b_id' => max($ross->id, $carol->id),
            'status' => RelationshipStatusEnum::Ended->value,
        ]);
        $this->assertDatabaseHas('relationships', [
            'relationship_type_id' => RelationshipType::query()->where('key', RelationshipTypeEnum::Partner->value)->value('id'),
            'contact_a_id' => min($joey->id, $rachel->id),
            'contact_b_id' => max($joey->id, $rachel->id),
            'status' => RelationshipStatusEnum::Ended->value,
            'ended_on' => '2003-10-09 00:00:00',
        ]);
    }

    #[Test]
    public function it_adds_neighbour_as_the_one_custom_relationship_type(): void
    {
        new ExampleAccountSeeder()->run();

        $neighbour = RelationshipType::query()->where('is_system', false)->sole();

        $this->assertEquals('Neighbour', $neighbour->name);
        $this->assertEquals(RelationshipCategoryEnum::Other->value, $neighbour->relationshipCategory->key);
        $this->assertEquals(4, Relationship::query()->where('relationship_type_id', $neighbour->id)->count());
    }

    #[Test]
    public function it_gives_every_person_at_least_twenty_life_events(): void
    {
        new ExampleAccountSeeder()->run();

        $this->assertCount(0, Contact::query()->where('life_event_count', '<', 20)->pluck('first_name'));

        $wedding = LifeEvent::query()->where('title', 'Ross marries Emily')->sole();

        $this->assertEquals(9, $wedding->participant_count);
        $this->assertEquals(9, $wedding->participants()->count());
    }

    #[Test]
    public function it_uses_every_life_event_category_and_twenty_types(): void
    {
        new ExampleAccountSeeder()->run();

        $types = LifeEventType::query()->whereIn('id', LifeEvent::query()->select('life_event_type_id'))->get();

        $this->assertGreaterThanOrEqual(20, $types->count());
        $this->assertEquals(LifeEventCategory::query()->count(), $types->pluck('life_event_category_id')->unique()->count());
    }

    #[Test]
    public function it_dates_no_event_after_mr_heckles_died(): void
    {
        new ExampleAccountSeeder()->run();

        $heckles = Contact::query()->where('last_name', 'Heckles')->firstOrFail();
        $events = LifeEvent::query()
            ->whereIn('id', LifeEventParticipant::query()->where('participant_id', $heckles->id)->where('participant_type', $heckles->getMorphClass())->select('life_event_id'))
            ->get();

        $this->assertGreaterThanOrEqual(20, $events->count());
        $this->assertEquals('1995-10-05', $events->max('happened_on')->toDateString());
    }
}
