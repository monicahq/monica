<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_a_template_a_contact_can_be_written_down_with(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $this->assertDatabaseHas('structures', [
            'account_id' => $user->account_id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'entity_type_id' => null,
        ]);

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => null,
            'slug' => DefaultTemplateEnum::BasicContact->value,
            'is_system' => true,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    #[Test]
    public function it_publishes_the_template_so_nobody_has_to_before_using_it(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $version = Template::query()->sole()->publishedVersion;

        $this->assertSame(1, $version->version_number);
        $this->assertSame(VersionStatusEnum::Published, $version->status);
        $this->assertNotNull($version->published_at);
        $this->assertNull($version->change_note);
        $this->assertSame(0, $version->record_count);
    }

    #[Test]
    public function it_assembles_the_template_from_the_sections_it_is_read_in_the_order_of(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $assembly = Template::query()->sole()->publishedVersion->assembly;

        $this->assertCount(6, $assembly);

        foreach ($assembly as $row) {
            $this->assertSame(VersionStatusEnum::Published, $row->sectionVersion->status);
            $this->assertSame(1, $row->sectionVersion->version_number);
            $this->assertSame(1, $row->sectionVersion->usage_count);
            $this->assertTrue($row->sectionVersion->section->is_system);
            $this->assertNull($row->sectionVersion->section->getRawOriginal('name'));
        }
    }

    #[Test]
    public function it_reads_the_template_in_the_pages_the_product_supplies(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $pages = Template::query()->sole()->publishedVersion->pages;

        $this->assertEquals(['general', 'ways-to-connect', 'information'], $pages->pluck('key')->all());
        $this->assertEquals([1, 2, 3], $pages->pluck('position')->all());

        foreach ($pages as $page) {
            $this->assertNull($page->getRawOriginal('label'));
        }
    }

    #[Test]
    public function it_puts_each_section_on_the_page_it_belongs_to(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $pages = Template::query()->sole()->publishedVersion->pages;

        $sectionsOf = fn (int $index): array => $pages[$index]->assembly
            ->map(fn ($row): string => $row->sectionVersion->section->name)
            ->all();

        $this->assertCount(2, $sectionsOf(0));
        $this->assertCount(2, $sectionsOf(1));
        $this->assertCount(2, $sectionsOf(2));

        foreach ($pages as $page) {
            $this->assertEquals(range(1, $page->assembly->count()), $page->assembly->pluck('position')->all());
        }
    }

    #[Test]
    public function it_seeds_every_section_wearing_its_mark(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        foreach (DefaultSectionEnum::cases() as $case) {
            $this->assertDatabaseHas('sections', [
                'account_id' => $user->account_id,
                'slug' => $case->value,
                'icon' => $case->icon(),
                'colour' => $case->colour()->value,
            ]);
        }
    }

    #[Test]
    public function it_seeds_no_glyph_a_section_may_not_be_marked_with(): void
    {
        foreach (DefaultSectionEnum::cases() as $case) {
            $this->assertContains($case->icon(), Section::ICONS, "{$case->value} asks for a glyph outside the set.");
        }
    }

    #[Test]
    public function it_asks_the_sections_for_the_fields_they_hold(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        foreach (DefaultSectionEnum::cases() as $case) {
            $version = Section::query()->firstWhere('slug', $case->value)->publishedVersion;

            $this->assertEquals(
                array_map(fn (DefaultFieldEnum $field): string => $field->value, $case->fields()),
                $version->fields()->whereNull('parent_field_id')->pluck('key')->all(),
            );
        }
    }

    #[Test]
    public function it_lets_the_documents_the_photos_and_the_notes_be_added_to_again(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $document = Field::query()->firstWhere('key', DefaultFieldEnum::Document->value);
        $photo = Field::query()->firstWhere('key', DefaultFieldEnum::Photo->value);
        $notes = Field::query()->firstWhere('key', DefaultFieldEnum::Notes->value);
        $emails = Field::query()->firstWhere('key', DefaultFieldEnum::Emails->value);
        $phones = Field::query()->firstWhere('key', DefaultFieldEnum::Phones->value);
        $addresses = Field::query()->firstWhere('key', DefaultFieldEnum::Addresses->value);

        foreach ([$document, $photo, $notes, $emails, $phones, $addresses] as $repeater) {
            $this->assertSame(FieldTypeEnum::Repeater, $repeater->type);
            $this->assertTrue($repeater->is_repeatable);
            $this->assertNull($repeater->repeat_min);
            $this->assertNull($repeater->repeat_max);
            $this->assertNull($repeater->parent_field_id);
        }

        $this->assertEquals([1, 2], $document->children->pluck('position')->all());
        $this->assertEquals([1, 2], $photo->children->pluck('position')->all());
        $this->assertEquals([1], $notes->children->pluck('position')->all());

        $this->assertEquals(['file', 'description'], $document->children->pluck('key')->all());
        $this->assertEquals(['image', 'caption'], $photo->children->pluck('key')->all());
        $this->assertEquals(['note'], $notes->children->pluck('key')->all());
        $this->assertEquals(['email'], $emails->children->pluck('key')->all());
        $this->assertEquals(['phone'], $phones->children->pluck('key')->all());
        $this->assertEquals(['address'], $addresses->children->pluck('key')->all());
        $this->assertSame(FieldTypeEnum::File, $document->children[0]->type);
        $this->assertSame(FieldTypeEnum::Image, $photo->children[0]->type);
        $this->assertSame(FieldTypeEnum::Textarea, $notes->children[0]->type);
        $this->assertSame(FieldTypeEnum::Email, $emails->children[0]->type);
        $this->assertNotNull($emails->children[0]->value_list_id);
        $this->assertSame(FieldTypeEnum::Phone, $phones->children[0]->type);
        $this->assertNotNull($phones->children[0]->value_list_id);

        $this->assertEquals(
            ['notes', 'emails', 'phones', 'addresses', 'document', 'photo'],
            Field::query()->where('is_repeatable', true)->orderBy('id')->pluck('key')->all(),
        );
    }

    #[Test]
    public function it_points_the_supplied_address_field_at_the_address_labels_list_of_the_account(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $address = Field::query()->firstWhere('key', DefaultFieldEnum::Address->value);
        $addressLabels = ValueList::query()
            ->where('account_id', $user->account_id)
            ->where('key', ValueListEnum::AddressLabels->value)
            ->first();

        $this->assertSame(FieldTypeEnum::Address, $address->type);
        $this->assertEquals($addressLabels->id, $address->value_list_id);
        $this->assertSame(DefaultFieldEnum::Addresses->value, $address->parent?->key);
    }

    #[Test]
    public function it_says_what_may_be_uploaded_into_the_fields_that_take_a_file(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $file = Field::query()->firstWhere('key', DefaultFieldEnum::File->value);
        $image = Field::query()->firstWhere('key', DefaultFieldEnum::Image->value);

        $this->assertContains('application/pdf', $file->options['accepted_mime_types']);
        $this->assertEquals(
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            $image->options['accepted_mime_types'],
        );

        $this->assertEquals(
            ['file', 'image'],
            Field::query()->whereNotNull('options')->orderBy('id')->pluck('key')->all(),
        );
    }

    #[Test]
    public function it_makes_the_notes_collect_at_the_top_and_leaves_the_rest_collecting_at_the_end(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $orderOf = fn (DefaultFieldEnum $field): ?RepetitionOrderEnum => Field::query()
            ->where('account_id', $user->account_id)
            ->where('key', $field->value)
            ->sole()
            ->repetition_order;

        $this->assertSame(RepetitionOrderEnum::NewestFirst, $orderOf(DefaultFieldEnum::Notes));
        $this->assertNull($orderOf(DefaultFieldEnum::Document));
        $this->assertNull($orderOf(DefaultFieldEnum::Photo));
    }

    #[Test]
    public function it_requires_none_of_them_so_a_contact_may_be_written_down_in_a_hurry(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $fields = Field::query()->where('account_id', $user->account_id)->get();

        $this->assertCount(18, $fields);
        $this->assertEmpty($fields->filter(fn (Field $field): bool => $field->is_required));
        $this->assertEmpty($fields->filter(fn (Field $field): bool => $field->is_sensitive));
    }

    #[Test]
    public function it_highlights_what_the_rest_of_the_contact_is_read_in_the_light_of(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $highlighted = Field::query()->where('is_highlighted', true)->orderBy('id')->pluck('key')->all();

        $this->assertEquals(['category', 'birthday'], $highlighted);
    }

    #[Test]
    public function it_offers_the_birthday_to_whatever_reminds_people_of_them(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $birthday = Field::query()->firstWhere('key', DefaultFieldEnum::Birthday->value);

        $this->assertSame(FieldTypeEnum::RecurringDate, $birthday->type);
        $this->assertTrue($birthday->type->canDriveAReminder());
    }

    #[Test]
    public function it_points_the_choices_at_the_lists_the_account_was_seeded_with(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $category = Field::query()->firstWhere('key', DefaultFieldEnum::Category->value);
        $gender = Field::query()->firstWhere('key', DefaultFieldEnum::Gender->value);

        $this->assertSame(FieldTypeEnum::Multiselect, $category->type);
        $this->assertEquals(ValueListEnum::ContactCategories->value, $category->valueList->key);
        $this->assertSame(FieldTypeEnum::Select, $gender->type);
        $this->assertEquals(ValueListEnum::Genders->value, $gender->valueList->key);
    }

    #[Test]
    public function it_freezes_the_lists_the_sections_read_their_choices_from(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $version = Section::query()->firstWhere('slug', DefaultSectionEnum::PersonalDetails->value)->publishedVersion;
        $categories = ValueList::query()->firstWhere('key', ValueListEnum::ContactCategories->value);

        $this->assertCount(2, $version->resolved_value_lists);
        $this->assertEquals(
            ValueListEnum::ContactCategories->value,
            $version->resolved_value_lists[$categories->id]['key'],
        );
        $this->assertCount(6, $version->resolved_value_lists[$categories->id]['items']);
    }

    #[Test]
    public function it_reads_the_supplied_template_in_the_language_of_whoever_is_looking(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $this->app->setLocale('fr_FR');

        $this->assertEquals('Contact de base', Template::query()->sole()->name);
        $this->assertEquals(
            'Coordonnées',
            Section::query()->firstWhere('slug', DefaultSectionEnum::ContactInformation->value)->name,
        );
        $this->assertEquals(
            'Façons de se connecter',
            Template::query()->sole()->publishedVersion->pages[1]->label,
        );
        $this->assertEquals(
            'Anniversaire',
            Field::query()->firstWhere('key', DefaultFieldEnum::Birthday->value)->label,
        );
    }

    #[Test]
    public function it_leaves_the_templates_of_other_accounts_alone(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        $elsewhere = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

        $this->assertEquals(0, Template::query()->where('account_id', $elsewhere->account_id)->count());
        $this->assertEquals(0, Section::query()->where('account_id', $elsewhere->account_id)->count());
    }

    #[Test]
    public function it_logs_the_seeding_once_rather_than_section_by_section(): void
    {
        $user = $this->userWithValueLists();

        Queue::fake();

        new CreateDefaultTemplate(user: $user)->handle();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::DefaultTemplateSeeded
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->isAbout(Template::query()->sole()),
        );
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_points_the_vaults_of_the_account_at_the_version_it_published(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        $family = Vault::factory()->create(['account_id' => $user->account_id]);
        $work = Vault::factory()->create(['account_id' => $user->account_id]);

        new CreateDefaultTemplate(user: $user)->handle();

        $version = Template::query()->sole()->structure->published_version_id;

        foreach ([$family, $work] as $vault) {
            $this->assertDatabaseHas('vault_default_templates', [
                'account_id' => $user->account_id,
                'vault_id' => $vault->id,
                'applies_to' => StructureAppliesToEnum::Contact->value,
                'entity_type_id' => null,
                'structure_version_id' => $version,
            ]);
        }
    }

    #[Test]
    public function it_marks_the_email_and_phone_fields_as_identifying(): void
    {
        $user = $this->userWithValueLists();

        new CreateDefaultTemplate($user)->handle();

        $email = Field::query()->where('account_id', $user->account_id)->where('type', FieldTypeEnum::Email)->firstOrFail();
        $phone = Field::query()->where('account_id', $user->account_id)->where('type', FieldTypeEnum::Phone)->firstOrFail();

        $this->assertTrue($email->is_identifying);
        $this->assertTrue($phone->is_identifying);
    }

    #[Test]
    public function it_leaves_every_other_supplied_field_alone(): void
    {
        $user = $this->userWithValueLists();

        new CreateDefaultTemplate($user)->handle();

        $others = Field::query()
            ->where('account_id', $user->account_id)
            ->whereNotIn('type', [FieldTypeEnum::Email->value, FieldTypeEnum::Phone->value])
            ->get();

        $this->assertTrue($others->isNotEmpty());
        $this->assertTrue($others->every(fn (Field $field): bool => $field->is_identifying === false));
    }

    #[Test]
    public function it_gives_the_account_an_animal_type_before_its_template(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'slug' => 'animals',
            'icon' => 'pet',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'slug' => 'basic-animal',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_restricts_the_animal_template_to_that_type(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $type = EntityType::query()->where('account_id', $user->account_id)->sole();
        $structure = $this->animalTemplate($user)->structure;

        $this->assertSame(StructureAppliesToEnum::Entity, $structure->applies_to);
        $this->assertSame($type->id, $structure->entity_type_id);
    }

    #[Test]
    public function it_publishes_the_animal_template_and_each_of_its_sections(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $version = $this->animalTemplate($user)->structure->publishedVersion;

        $this->assertSame(1, $version->version_number);
        $this->assertSame(VersionStatusEnum::Published, $version->status);

        foreach ($version->assembly as $row) {
            $this->assertSame(VersionStatusEnum::Published, $row->sectionVersion->status);
            $this->assertSame(1, $row->sectionVersion->version_number);
        }
    }

    #[Test]
    public function it_lays_the_animal_template_out_as_supplied(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $version = $this->animalTemplate($user)->structure->publishedVersion;
        $pages = $version->pages()->orderBy('position')->get();

        $this->assertSame(['general'], $pages->pluck('key')->all());

        $sections = $version->assembly()->get();

        $this->assertSame(
            ['animal-details', 'animal-people', 'animal-care', 'animal-notes'],
            $sections->map(fn ($row): string => $row->sectionVersion->section->slug)->all(),
        );

        $fields = $sections->flatMap(fn ($row) => $row->sectionVersion->fields()->orderBy('parent_field_id')->orderBy('position')->get())
            ->mapWithKeys(fn (Field $field): array => [$field->key => $field->type])
            ->all();

        $this->assertEquals([
            'species' => FieldTypeEnum::Text,
            'breed' => FieldTypeEnum::Text,
            'sex' => FieldTypeEnum::Select,
            'birthday' => FieldTypeEnum::RecurringDate,
            'adoption_date' => FieldTypeEnum::RecurringDate,
            'microchip_number' => FieldTypeEnum::Text,
            'caregivers' => FieldTypeEnum::Repeater,
            'caregiver_person' => FieldTypeEnum::Relation,
            'caregiver_role' => FieldTypeEnum::Text,
            'food_and_routine' => FieldTypeEnum::Textarea,
            'health_and_care' => FieldTypeEnum::Textarea,
            'animal_notes' => FieldTypeEnum::Textarea,
        ], $fields);
    }

    #[Test]
    public function it_offers_female_male_and_unknown_with_none_chosen(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $sex = Field::query()->where('account_id', $user->account_id)->where('key', 'sex')->sole();

        $this->assertNull($sex->default_value);
        $this->assertSame(ValueListEnum::AnimalSexes->value, $sex->valueList->key);
        $this->assertSame(['female', 'male', 'unknown'], $sex->valueList->items()->orderBy('position')->pluck('key')->all());
    }

    #[Test]
    public function it_asks_for_a_person_on_every_caregiver_row_and_nothing_else(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $person = Field::query()->where('account_id', $user->account_id)->where('key', 'caregiver_person')->sole();

        $this->assertTrue($person->is_required);
        $this->assertSame(RelationTargetEnum::Contact, $person->relation_target);
        $this->assertTrue($person->options['distinct_in_list']);
        $this->assertSame('caregivers', $person->parent->key);
        $this->assertFalse($person->is_unique);

        $this->assertSame(
            ['caregiver_person'],
            Field::query()
                ->where('account_id', $user->account_id)
                ->where('is_required', true)
                ->pluck('key')
                ->all(),
        );
    }

    #[Test]
    public function it_sets_the_animal_default_of_every_vault(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        $vaults = Vault::factory()->count(2)->create(['account_id' => $user->account_id]);

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $template = $this->animalTemplate($user);

        foreach ($vaults as $vault) {
            $this->assertDatabaseHas('vault_default_templates', [
                'vault_id' => $vault->id,
                'applies_to' => StructureAppliesToEnum::Entity->value,
                'entity_type_id' => $template->structure->entity_type_id,
                'structure_version_id' => $template->structure->published_version_id,
            ]);
        }
    }

    #[Test]
    public function it_keeps_a_default_somebody_chose(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $chosen = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        VaultDefaultTemplate::query()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'entity_type_id' => null,
            'structure_version_id' => $chosen->id,
        ]);

        new CreateDefaultTemplate(user: $user)->handle();

        $this->assertSame(
            $chosen->id,
            VaultDefaultTemplate::query()->where('vault_id', $vault->id)->sole()->structure_version_id,
        );
    }

    #[Test]
    public function it_writes_nothing_the_second_time(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        Vault::factory()->create(['account_id' => $user->account_id]);

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $this->assertSame(1, EntityType::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(1, Template::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(4, Section::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(1, VaultDefaultTemplate::query()->where('account_id', $user->account_id)->count());
    }

    private function animalTemplate(User $user): Template
    {
        return Template::query()
            ->where('account_id', $user->account_id)
            ->where('slug', DefaultTemplateEnum::BasicAnimal->value)
            ->sole();
    }

    private function userWithValueLists(): User
    {
        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        return $user;
    }
}
