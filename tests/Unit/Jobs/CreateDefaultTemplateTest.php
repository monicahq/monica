<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
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

        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'entity_type_id' => null,
            'name' => null,
            'name_translation_key' => 'Basic contact',
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

    /**
     * A tab is seeded only when there is something to put on it. Everything
     * else a contact page eventually holds is its own model with its own
     * screens, and a tab opening on nothing would be worse than no tab.
     */
    #[Test]
    public function it_reads_the_template_in_the_pages_the_product_supplies(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $pages = Template::query()->sole()->publishedVersion->pages;

        $this->assertEquals(['general', 'ways-to-connect', 'information'], $pages->pluck('key')->all());
        $this->assertEquals(['General', 'Ways to connect', 'Information'], $pages->map(fn ($page): string => $page->label)->all());
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

        $this->assertEquals(['Personal details', 'Notes'], $sectionsOf(0));
        $this->assertEquals(['Contact information', 'Addresses'], $sectionsOf(1));
        $this->assertEquals(['Documents', 'Photos'], $sectionsOf(2));

        foreach ($pages as $page) {
            $this->assertEquals(range(1, $page->assembly->count()), $page->assembly->pluck('position')->all());
        }
    }

    #[Test]
    public function it_asks_the_sections_for_the_fields_they_hold(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

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

        foreach ([$document, $photo, $notes] as $repeater) {
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
        $this->assertSame(FieldTypeEnum::File, $document->children[0]->type);
        $this->assertSame(FieldTypeEnum::Image, $photo->children[0]->type);
        $this->assertSame(FieldTypeEnum::Textarea, $notes->children[0]->type);

        $this->assertEquals(
            ['notes', 'document', 'photo'],
            Field::query()->where('is_repeatable', true)->orderBy('id')->pluck('key')->all(),
        );
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

    /**
     * The whole point of notes reading newest first: somebody adding one sees it
     * without scrolling past everything they wrote before.
     */
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

    /**
     * A direction on a field that is not a repeater would be a setting its type
     * cannot honour.
     */
    #[Test]
    public function it_gives_no_direction_to_a_field_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $notRepeaters = Field::query()
            ->where('account_id', $user->account_id)
            ->where('is_repeatable', false)
            ->whereNotNull('repetition_order')
            ->pluck('key')
            ->all();

        $this->assertSame([], $notRepeaters);
    }

    /**
     * The point of the change that made this a repeater: somebody keeps learning
     * things about a person, and one box would mean editing what they wrote last
     * time in order to write down what they learned this time.
     */
    #[Test]
    public function it_lets_a_contact_hold_as_many_notes_as_somebody_writes(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $notes = Field::query()->firstWhere('key', DefaultFieldEnum::Notes->value);

        $this->assertTrue($notes->is_repeatable);
        $this->assertNull($notes->repeat_max, 'A contact may hold any number of notes.');
        $this->assertCount(1, $notes->children, 'One note is the note, and nothing else.');
        $this->assertSame(FieldTypeEnum::Textarea, $notes->children[0]->type);
    }

    #[Test]
    public function it_puts_the_notes_on_the_tab_a_contact_opens_on(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $pages = Template::query()->sole()->publishedVersion->pages;

        $sectionsOf = fn (int $index): array => $pages[$index]->assembly
            ->map(fn ($row): string => $row->sectionVersion->section->name)
            ->all();

        $this->assertContains('Notes', $sectionsOf(0));
        $this->assertNotContains('Notes', $sectionsOf(2));
    }

    #[Test]
    public function it_requires_none_of_them_so_a_contact_may_be_written_down_in_a_hurry(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $fields = Field::query()->where('account_id', $user->account_id)->get();

        $this->assertCount(15, $fields);
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
        $this->assertTrue($birthday->is_reminder_source);
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

        $version = Template::query()->sole()->published_version_id;

        foreach ([$family, $work] as $vault) {
            $this->assertDatabaseHas('vault_default_templates', [
                'account_id' => $user->account_id,
                'vault_id' => $vault->id,
                'applies_to' => TemplateAppliesToEnum::Contact->value,
                'entity_type_id' => null,
                'template_version_id' => $version,
            ]);
        }
    }

    #[Test]
    public function it_leaves_the_vaults_of_another_account_alone(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();
        $elsewhere = Vault::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $elsewhere->id)->count());
    }

    private function userWithValueLists(): User
    {
        $user = User::factory()->create();

        new CreateDefaultValueLists(user: $user)->execute();

        return $user;
    }
}
