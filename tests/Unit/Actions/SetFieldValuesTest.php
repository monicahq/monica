<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetFieldValues;
use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\FieldIsRequired;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetFieldValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_a_whole_page_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $notes = $this->fieldOn($contact, ['key' => 'notes', 'type' => FieldTypeEnum::Textarea]);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$city->id => 'Scranton', $notes->id => 'Sells paper.'],
        )->execute();

        $this->assertSame(2, $written);
        $this->assertDatabaseHas('field_values', ['field_id' => $city->id, 'value_string' => 'Scranton']);
        $this->assertDatabaseHas('field_values', ['field_id' => $notes->id, 'value_text' => 'Sells paper.']);
    }

    #[Test]
    public function it_clears_a_field_somebody_emptied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$city->id => 'Scranton'],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$city->id => ''],
        )->execute();

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_leaves_a_field_that_was_already_empty_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$city->id => null],
        )->execute();

        $this->assertSame(0, $written);
    }

    #[Test]
    public function it_keeps_a_no_because_a_no_is_an_answer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Boolean]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$field->id => false],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_boolean' => false]);
    }

    #[Test]
    public function it_writes_the_item_a_choice_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Select, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$field->id => (string) $item->id],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_list_item_id' => $item->id]);
    }

    #[Test]
    public function it_writes_every_item_a_multiple_choice_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $first = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $second = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$field->id => [(string) $first->id, (string) $second->id]],
        )->execute();

        $value = FieldValue::query()->where('field_id', $field->id)->firstOrFail();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $value->value_list_item_ids);
    }

    #[Test]
    public function it_carries_the_unknown_year_of_a_date_that_comes_round_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$field->id => '1964-03-15'],
            yearIsUnknown: [$field->id => true],
        )->execute();

        $value = FieldValue::query()->where('field_id', $field->id)->firstOrFail();

        $this->assertTrue($value->year_is_unknown);
        $this->assertEquals(FieldValue::PLACEHOLDER_YEAR, (int) $value->value_date->format('Y'));
    }

    #[Test]
    public function it_refuses_a_required_field_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['is_required' => true, 'label' => 'City']);

        $this->expectException(FieldIsRequired::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$field->id => ''],
        )->execute();
    }

    #[Test]
    public function it_writes_nothing_at_all_when_one_required_field_is_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $required = $this->fieldOn($contact, ['key' => 'diet', 'is_required' => true]);

        try {
            new SetFieldValues(
                user: $user,
                record: $contact,
                page: $this->pageOf($contact),
                values: [$city->id => 'Scranton', $required->id => ''],
            )->execute();
        } catch (FieldIsRequired) {
            // The refusal is the point; what matters is what it left behind.
        }

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_leaves_a_type_it_has_no_control_for_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $relation = $this->fieldOn($contact, ['type' => FieldTypeEnum::Relation, 'is_required' => true]);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            page: $this->pageOf($contact),
            values: [$relation->id => 'anything'],
        )->execute();

        $this->assertSame(0, $written);
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValues(
            user: User::factory()->create(),
            record: $contact,
            page: $this->pageOf($contact),
            values: [],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_page_of_another_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = TemplateVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            page: $elsewhere->pages->first(),
            values: [],
        )->execute();
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => TemplateVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    private function pageOf(Contact $contact): TemplateVersionPage
    {
        return $contact->templateVersion->pages->first();
    }

    /**
     * Put a field on the page the contact renders from, the way the builder
     * does it: on a draft, which is then published.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function fieldOn(Contact $contact, array $attributes = []): Field
    {
        $version = $contact->templateVersion;
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create(array_merge([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ], $attributes));

        TemplateVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'template_version_id' => $version->id,
            'template_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $version->status = VersionStatusEnum::Published;
        $version->save();

        $contact->unsetRelation('templateVersion');

        return $field;
    }
}
