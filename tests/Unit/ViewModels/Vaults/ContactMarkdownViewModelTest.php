<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactMarkdownViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ContactMarkdownViewModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-13 10:00:00');

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Scranton']);
    }

    #[Test]
    public function it_opens_with_the_name_and_where_the_contact_comes_from(): void
    {
        $dwight = $this->contact(['first_name' => 'Dwight', 'last_name' => 'Schrute', 'nickname' => 'D']);

        $document = $this->document($dwight);

        $this->assertStringStartsWith("# Dwight Schrute\n\nExported on ", $document);
        $this->assertStringContainsString('from the vault Scranton.', $document);
        $this->assertStringContainsString("## Names\n\n- **Nickname**: D", $document);
    }

    #[Test]
    public function it_writes_the_fields_under_their_page_and_section(): void
    {
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Text, 'label' => 'Favourite crop'],
            ['type' => FieldTypeEnum::Text, 'label' => 'Empty field'],
        ]);
        $dwight = $this->contact(['structure_version_id' => $version->id]);
        $this->value($dwight, $this->field($version, 'Favourite crop'), 'Beets');

        $document = $this->document($dwight);

        $this->assertStringContainsString("## Contact\n\n### ", $document);
        $this->assertStringContainsString('- **Favourite crop**: Beets', $document);
        $this->assertStringNotContainsString('Empty field', $document);
    }

    #[Test]
    public function it_keeps_sensitive_values_only_for_a_reader_who_reaches_them(): void
    {
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Text, 'label' => 'Safe combination', 'is_sensitive' => true],
        ]);
        $dwight = $this->contact(['structure_version_id' => $version->id]);
        $this->value($dwight, $this->field($version, 'Safe combination'), '1-2-3-4');

        $this->assertStringContainsString('1-2-3-4', $this->document($dwight, reachesSensitiveFields: true));
        $this->assertStringNotContainsString('1-2-3-4', $this->document($dwight, reachesSensitiveFields: false));
        $this->assertStringNotContainsString('Safe combination', $this->document($dwight, reachesSensitiveFields: false));
    }

    #[Test]
    public function it_writes_every_row_of_a_repeating_field(): void
    {
        $version = Structure::withFields($this->user, [['type' => FieldTypeEnum::Text, 'label' => 'Anchor']]);
        $sectionVersionId = $this->field($version, 'Anchor')->section_version_id;
        $repeater = Field::factory()->create(['section_version_id' => $sectionVersionId, 'label' => 'Notes', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true, 'position' => 2]);
        $child = Field::factory()->create(['section_version_id' => $sectionVersionId, 'parent_field_id' => $repeater->id, 'type' => FieldTypeEnum::Textarea, 'label' => 'Note', 'position' => 1]);
        $dwight = $this->contact(['structure_version_id' => $version->id]);

        foreach (['Bears eat beets.', "Bears.\n# Beets.\n- Battlestar Galactica."] as $position => $text) {
            $row = FieldValueRow::factory()->create(['valuable_id' => $dwight->id, 'field_id' => $repeater->id, 'position' => $position + 1]);
            FieldValue::factory()->create(['valuable_id' => $dwight->id, 'account_id' => $dwight->account_id, 'vault_id' => $dwight->vault_id, 'field_id' => $child->id, 'field_value_row_id' => $row->id, 'value_string' => null, 'value_text' => $text]);
        }

        $document = $this->document($dwight);

        $this->assertStringContainsString("- **Notes**\n  - Bears eat beets.\n  - Bears.\n    \\# Beets.\n    \\- Battlestar Galactica.", $document);
    }

    #[Test]
    public function it_keeps_a_value_from_breaking_the_structure_of_the_document(): void
    {
        $version = Structure::withFields($this->user, [['type' => FieldTypeEnum::Textarea, 'label' => 'Motto']]);
        $dwight = $this->contact(['structure_version_id' => $version->id]);
        FieldValue::factory()->create([
            'valuable_id' => $dwight->id, 'account_id' => $dwight->account_id, 'vault_id' => $dwight->vault_id,
            'field_id' => $this->field($version, 'Motto')->id, 'value_string' => null,
            'value_text' => "Whenever I'm about to do something,\n## I think\n1. Would an idiot do that?",
        ]);

        $this->assertStringContainsString(
            "- **Motto**: Whenever I'm about to do something,\n  \\## I think\n  1\\. Would an idiot do that?",
            $this->document($dwight),
        );
    }

    #[Test]
    public function it_writes_the_relationships_under_their_category(): void
    {
        $dwight = $this->contact(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $mose = $this->contact(['first_name' => 'Mose', 'last_name' => 'Schrute']);
        $type = RelationshipType::factory()->create(['account_id' => $this->user->account_id, 'role_a' => 'Cousin', 'role_b' => 'Cousin']);
        Relationship::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'relationship_type_id' => $type->id, 'contact_a_id' => $dwight->id, 'contact_b_id' => $mose->id]);

        $document = $this->document($dwight);

        $this->assertStringContainsString("## Relationships\n\n### {$type->relationshipCategory->name}\n\n- **Cousin**: Mose Schrute", $document);
    }

    #[Test]
    public function it_writes_the_life_events_newest_first_with_who_was_there(): void
    {
        $dwight = $this->contact(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $angela = $this->contact(['first_name' => 'Angela', 'last_name' => 'Martin']);
        $this->lifeEvent($dwight, '2010-05-01', 'Bought Schrute Farms');
        $newer = $this->lifeEvent($dwight, '2013-05-16', 'Wedding', 'On the beet farm.');
        LifeEventParticipant::factory()->create(['life_event_id' => $newer->id, 'participant_id' => $angela->id, 'position' => 2]);

        $document = $this->document($dwight);

        $this->assertStringContainsString('## Life events', $document);
        $this->assertMatchesRegularExpression('/- \*\*16\/05\/2013\*\*: Wedding \(.+\)\n  - With Angela Martin\n  - On the beet farm\./', $document);
        $this->assertLessThan(strpos($document, 'Bought Schrute Farms'), strpos($document, 'Wedding'));
    }

    #[Test]
    public function it_writes_the_groups_the_reminders_and_the_loans(): void
    {
        $dwight = $this->contact(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        GroupMember::factory()->create(['contact_id' => $dwight->id, 'role' => 'Owner', 'group_id' => Group::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Schrute farm'])->id]);
        $reminder = Reminder::factory()->manual($dwight, '2026-12-01')->once()->create(['name' => 'Harvest the beets', 'timezone' => 'UTC']);
        ReminderOccurrence::factory()->for($reminder)->create(['local_date' => '2026-12-01']);
        PossessionLoan::factory()->active()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Stapler'])->id,
            'contact_id' => $dwight->id,
            'started_on' => '2026-11-01',
        ]);

        $document = $this->document($dwight);

        $this->assertStringContainsString("## Groups\n\n- **Schrute farm**: Owner", $document);
        $this->assertStringContainsString("## Reminders\n\n- **Harvest the beets**: once and no more, Next on ", $document);
        $this->assertStringContainsString("## Loans\n\n- **Stapler**: ", $document);
    }

    #[Test]
    public function it_leaves_out_every_part_that_holds_nothing(): void
    {
        $toby = $this->contact(['first_name' => 'Toby', 'last_name' => 'Flenderson', 'nickname' => null]);

        $document = $this->document($toby);

        foreach (['## Names', '## Relationships', '## Life events', '## Groups', '## Reminders', '## Loans'] as $heading) {
            $this->assertStringNotContainsString($heading, $document);
        }
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            ...$attributes,
        ]);
    }

    private function document(Contact $contact, bool $reachesSensitiveFields = true): string
    {
        return new ContactMarkdownViewModel(
            user: $this->user,
            vault: $this->vault,
            contact: $contact->refresh(),
            reachesSensitiveFields: $reachesSensitiveFields,
        )->document();
    }

    private function field(StructureVersion $version, string $label): Field
    {
        return Field::query()
            ->whereIn('section_version_id', $version->sectionVersions()->select('section_versions.id'))
            ->where('label', $label)
            ->sole();
    }

    private function value(Contact $contact, Field $field, string $value): void
    {
        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => $value,
        ]);
    }

    private function lifeEvent(Contact $contact, string $happenedOn, string $title, ?string $narrative = null): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $contact->vault_id,
            'account_id' => $contact->account_id,
            'happened_on' => $happenedOn,
            'title' => $title,
            'narrative' => $narrative,
        ]);

        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id, 'position' => 1]);

        return $lifeEvent;
    }
}
