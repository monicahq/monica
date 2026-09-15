<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WriteDefaultValues;
use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WriteDefaultValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_record_the_answer_its_structure_starts_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['key' => 'city', 'default_value' => 'Scranton']);

        $written = new WriteDefaultValues(user: $user, record: $contact)->execute();

        $this->assertSame(1, $written);
        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_string' => 'Scranton']);
    }

    #[Test]
    public function it_writes_nothing_for_a_field_with_no_default(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $this->fieldOn($contact, ['key' => 'city', 'default_value' => null]);

        $this->assertSame(0, new WriteDefaultValues(user: $user, record: $contact)->execute());
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_starts_a_choice_on_the_item_the_field_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, [
            'type' => FieldTypeEnum::Select,
            'value_list_id' => $list->id,
            'default_value' => $item->id,
        ]);

        new WriteDefaultValues(user: $user, record: $contact)->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_list_item_id' => $item->id]);
    }

    #[Test]
    public function it_ignores_a_default_naming_an_item_nobody_offers_any_more(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $retired = ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
        ]);
        $this->fieldOn($contact, [
            'type' => FieldTypeEnum::Select,
            'value_list_id' => $list->id,
            'default_value' => $retired->id,
        ]);

        $this->assertSame(0, new WriteDefaultValues(user: $user, record: $contact)->execute());
    }

    #[Test]
    public function it_leaves_a_type_it_cannot_write_a_default_for_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $this->fieldOn($contact, ['type' => FieldTypeEnum::Relation, 'default_value' => 'anything']);

        $this->assertSame(0, new WriteDefaultValues(user: $user, record: $contact)->execute());
    }

    #[Test]
    public function it_is_what_gives_a_new_contact_its_defaults(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['key' => 'city', 'default_value' => 'Scranton']);

        $written = new WriteDefaultValues(user: $user, record: $contact)->execute();

        $this->assertSame(1, $written);
        $this->assertEquals(
            'Scranton',
            FieldValue::query()->where('field_id', $field->id)->firstOrFail()->value_string,
        );
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function fieldOn(Contact $contact, array $attributes = []): Field
    {
        $version = $contact->structureVersion;
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create(array_merge([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ], $attributes));

        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $version->status = VersionStatusEnum::Published;
        $version->save();

        $contact->unsetRelation('structureVersion');

        return $field;
    }
}
