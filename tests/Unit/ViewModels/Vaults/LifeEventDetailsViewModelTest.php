<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\LifeEventSourceEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\LifeEventDetailsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventDetailsViewModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $michael;

    private LifeEventType $wedding;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-24 10:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->actingAs($this->user);
        $this->michael = $this->contactNamed('Michael', ['is_me' => true]);
        $celebration = LifeEventCategory::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Celebration']);
        $this->wedding = LifeEventType::factory()->create(['account_id' => $this->user->account_id, 'life_event_category_id' => $celebration->id, 'name' => 'Wedding']);
    }

    #[Test]
    public function it_describes_the_event_and_lists_only_the_facts_it_holds(): void
    {
        $niagara = Group::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'name' => 'The wedding party']);
        $lifeEvent = $this->lifeEvent([
            'title' => 'Jim and Pam get married',
            'narrative' => "Everybody danced down the aisle.\nJim cut his tie.",
            'happened_at_time' => '15:30:00',
            'timezone' => 'America/Toronto',
            'duration_in_minutes' => 90,
            'location' => 'Niagara Falls',
            'group_id' => $niagara->id,
        ]);
        $this->fieldValue($lifeEvent, 'Officiant', position: 2, attributes: ['value_string' => 'Reverend Lynn']);
        $this->fieldValue($lifeEvent, 'Theme', position: 1, attributes: ['value_string' => 'Waterfalls']);
        $this->fieldValue($lifeEvent, 'Song', position: 3, attributes: ['value_string' => null]);

        $details = $this->details($lifeEvent);

        $this->assertSame('Jim and Pam get married', $details->title());
        $this->assertSame('Wedding', $details->typeName());
        $this->assertSame('Celebration', $details->categoryName());
        $this->assertSame("Everybody danced down the aisle.\nJim cut his tie.", $details->narrative());
        $this->assertSame(
            ['Date', 'Time', 'Duration', 'Place', 'Group', 'Theme', 'Officiant'],
            array_column($details->facts(), 'label'),
        );
        $this->assertSame('15:30, America/Toronto', $details->facts()[1]['value']);
        $this->assertSame('90 minutes', $details->facts()[2]['value']);
        $this->assertSame(route('groups.show', ['vault' => $this->vault, 'group' => $niagara]), $details->facts()[4]['url']);
    }

    #[Test]
    public function it_leaves_out_the_time_zone_when_it_is_the_readers_own(): void
    {
        $lifeEvent = $this->lifeEvent(['happened_at_time' => '09:00:00', 'timezone' => 'UTC']);

        $this->assertSame('09:00', $this->details($lifeEvent)->facts()[1]['value']);
    }

    #[Test]
    public function it_writes_the_date_as_precisely_as_it_is_known(): void
    {
        $year = $this->lifeEvent(['happened_on' => '2007-05-01', 'date_precision' => DatePrecisionEnum::Year]);
        $unknown = $this->lifeEvent(['date_precision' => DatePrecisionEnum::Unknown]);
        $stay = $this->lifeEvent(['happened_on' => '2007-05-01', 'ended_on' => '2007-05-04', 'date_precision' => DatePrecisionEnum::Day]);

        $this->assertSame('2007', $this->details($year)->facts()[0]['value']);
        $this->assertSame('Date unknown', $this->details($unknown)->facts()[0]['value']);
        $this->assertStringContainsString(' to ', $this->details($stay)->facts()[0]['value']);
    }

    #[Test]
    public function it_lists_who_was_there_with_their_role_notes_and_tie_to_the_vaults_own_person(): void
    {
        $jim = $this->contactNamed('Jim');
        $roy = $this->contactNamed('Roy', ['is_partial' => true]);
        $farm = EntityType::factory()->create(['account_id' => $this->user->account_id, 'name_singular' => 'Farm', 'name_plural' => 'Farms']);
        $schruteFarms = Entity::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'entity_type_id' => $farm->id, 'name' => 'Schrute Farms']);
        $bossOf = RelationshipType::factory()->create(['account_id' => $this->user->account_id, 'role_a' => 'Boss', 'role_b' => 'Employee', 'is_symmetric' => false]);
        Relationship::factory()->create(['vault_id' => $this->vault->id, 'relationship_type_id' => $bossOf->id, 'contact_a_id' => $this->michael->id, 'contact_b_id' => $jim->id]);

        $lifeEvent = $this->lifeEvent();
        $this->participant($lifeEvent, $jim, 0, ['role' => 'Groom', 'notes' => 'Lost his tie']);
        $this->participant($lifeEvent, $this->michael, 1);
        $this->participant($lifeEvent, $roy, 2);
        $this->participant($lifeEvent, $schruteFarms, 3);

        $people = $this->details($lifeEvent)->people();

        $this->assertSame(['Jim', 'Michael', 'Roy', 'Schrute Farms'], array_map(fn (array $person): string => mb_trim($person['name']), $people));
        $this->assertSame(['Employee', 'You', null, 'Farm'], array_column($people, 'relation'));
        $this->assertSame('Groom', $people[0]['role']);
        $this->assertSame('Lost his tie', $people[0]['notes']);
        $this->assertSame(route('contacts.show', ['vault' => $this->vault, 'contact' => $jim]), $people[0]['url']);
        $this->assertNull($people[2]['url']);
        $this->assertSame(route('entities.index', ['vault' => $this->vault, 'entityType' => $farm]), $people[3]['url']);
    }

    #[Test]
    public function it_puts_attached_files_under_files_rather_than_among_the_facts(): void
    {
        $lifeEvent = $this->lifeEvent();
        $file = File::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'original_name' => 'Seating plan.pdf', 'size_in_bytes' => 86016]);
        $this->fieldValue($lifeEvent, 'Invitation', position: 1, type: FieldTypeEnum::File, attributes: ['value_string' => null, 'value_file_id' => $file->id]);

        $details = $this->details($lifeEvent);

        $this->assertSame(['Date'], array_column($details->facts(), 'label'));
        $this->assertSame([[
            'name' => 'Seating plan.pdf',
            'size' => '84.0 KB',
            'url' => route('files.show', ['vault' => $this->vault, 'file' => $file]),
        ]], $details->files());
    }

    #[Test]
    public function it_says_where_the_event_was_added_and_when_it_last_changed(): void
    {
        $lifeEvent = $this->lifeEvent(['created_from' => LifeEventSourceEnum::Dashboard, 'created_at' => '2026-11-03 09:00:00', 'updated_at' => '2026-11-03 09:00:00']);

        $this->assertStringContainsString('from the dashboard', $this->details($lifeEvent)->added());
        $this->assertNull($this->details($lifeEvent)->changed());

        $lifeEvent->forceFill(['updated_at' => '2026-11-19 10:00:00'])->saveQuietly();

        $this->assertSame('Last changed 5 days ago.', $this->details($lifeEvent->fresh())->changed());
    }

    #[Test]
    public function it_reads_as_many_times_whatever_the_event_holds(): void
    {
        $small = $this->lifeEvent();
        $this->participant($small, $this->contactNamed('Jim'), 0);
        $this->fieldValue($small, 'Theme', position: 1);

        $large = $this->lifeEvent();
        foreach (['Jim', 'Pam', 'Dwight', 'Angela', 'Kevin'] as $position => $name) {
            $this->participant($large, $this->contactNamed($name), $position);
            $this->fieldValue($large, "Field {$name}", position: $position);
        }

        $this->assertSame($this->readsToDraw($small), $this->readsToDraw($large));
    }

    private function readsToDraw(LifeEvent $lifeEvent): int
    {
        $details = $this->details($lifeEvent->fresh());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $details->title();
        $details->facts();
        $details->people();
        $details->files();
        $details->added();
        $details->changed();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function details(LifeEvent $lifeEvent): LifeEventDetailsViewModel
    {
        return new LifeEventDetailsViewModel(user: $this->user, vault: $this->vault, lifeEvent: $lifeEvent);
    }

    /** @param array<string, mixed> $attributes */
    private function lifeEvent(array $attributes = []): LifeEvent
    {
        return LifeEvent::factory()->create([
            'vault_id' => $this->vault->id,
            'life_event_type_id' => $this->wedding->id,
            'happened_on' => '2007-05-01',
            ...$attributes,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function contactNamed(string $firstName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => $firstName,
            'last_name' => null,
            ...$attributes,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function participant(LifeEvent $lifeEvent, Contact|Entity $person, int $position, array $attributes = []): void
    {
        LifeEventParticipant::factory()->create([
            'life_event_id' => $lifeEvent->id,
            'participant_type' => $person->getMorphClass(),
            'participant_id' => $person->id,
            'position' => $position,
            ...$attributes,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function fieldValue(LifeEvent $lifeEvent, string $label, int $position, FieldTypeEnum $type = FieldTypeEnum::Text, array $attributes = []): void
    {
        $field = Field::factory()->create(['account_id' => $this->user->account_id, 'label' => $label, 'position' => $position, 'type' => $type]);

        FieldValue::factory()->create([
            'valuable_type' => $lifeEvent->getMorphClass(),
            'valuable_id' => $lifeEvent->id,
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'field_id' => $field->id,
            ...$attributes,
        ]);
    }
}
