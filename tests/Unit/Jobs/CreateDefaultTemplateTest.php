<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultSectionEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
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

        $this->assertEquals(
            ['Personal details', 'Contact details', 'Notes'],
            $assembly->map(fn ($row): string => $row->sectionVersion->section->name)->all(),
        );
        $this->assertEquals([1, 2, 3], $assembly->pluck('position')->all());

        foreach ($assembly as $row) {
            $this->assertSame(VersionStatusEnum::Published, $row->sectionVersion->status);
            $this->assertSame(1, $row->sectionVersion->version_number);
            $this->assertSame(1, $row->sectionVersion->usage_count);
            $this->assertTrue($row->sectionVersion->section->is_system);
            $this->assertNull($row->sectionVersion->section->getRawOriginal('name'));
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
                $version->fields()->pluck('key')->all(),
            );
        }
    }

    #[Test]
    public function it_requires_none_of_them_so_a_contact_may_be_written_down_in_a_hurry(): void
    {
        Queue::fake();

        $user = $this->userWithValueLists();

        new CreateDefaultTemplate(user: $user)->handle();

        $fields = Field::query()->where('account_id', $user->account_id)->get();

        $this->assertCount(7, $fields);
        $this->assertEmpty($fields->filter(fn (Field $field): bool => $field->is_required));
        $this->assertEmpty($fields->filter(fn (Field $field): bool => $field->is_sensitive));
        $this->assertEmpty($fields->filter(fn (Field $field): bool => $field->is_repeatable));
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
            Section::query()->firstWhere('slug', DefaultSectionEnum::ContactDetails->value)->name,
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
                && $job->loggable->is(Template::query()->sole()),
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
