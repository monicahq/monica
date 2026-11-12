<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactListColumnsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListColumnsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_from_the_defaults_when_the_vault_chose_nothing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $columns = new ContactListColumnsViewModel(user: $user, vault: $vault)->columns();

        $this->assertSame(['email', 'phone', 'last-spoke', 'template'], array_column($columns, 'source'));
        $this->assertSame([true, true, true, false], array_column($columns, 'isVisible'));
    }

    #[Test]
    public function it_splits_what_may_be_added_by_how_many_templates_carry_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company'], 'Origins' => ['how_we_met']]);
        $this->contactTemplateCarrying($user, 'Family', ['Origins' => ['how_we_met']]);

        $groups = new ContactListColumnsViewModel(user: $user, vault: $vault)->additions();

        $this->assertContains('How We Met', array_column($groups[0]['items'], 'label'));
        $this->assertSame(['Company'], array_column($groups[1]['items'], 'label'));
        $this->assertSame(['Professional'], $groups[1]['items'][0]['templates']);
    }

    #[Test]
    public function it_offers_no_field_the_list_cannot_show_or_already_shows_elsewhere(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = $this->contactTemplateCarrying($user, 'Basic contact', ['Details' => ['favourite_paper']])['Details'];
        $sectionVersion = SectionVersion::query()->where('section_id', $section->id)->sole();

        Field::factory()->sensitive()->create(['section_version_id' => $sectionVersion->id, 'key' => 'social_security']);
        Field::factory()->layout()->create(['section_version_id' => $sectionVersion->id, 'key' => 'divider']);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'key' => 'photo', 'type' => FieldTypeEnum::Image]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'key' => 'work_email', 'type' => FieldTypeEnum::Email]);
        Field::factory()->recurringDate()->create(['section_version_id' => $sectionVersion->id, 'key' => 'birthday']);

        $offered = array_column(array_merge(...array_column(
            new ContactListColumnsViewModel(user: $user, vault: $vault)->additions(),
            'items',
        )), 'column');

        $this->assertSame(['favourite_paper'], array_values(array_filter(array_column($offered, 'fieldKey'))));
    }

    #[Test]
    public function it_marks_a_column_no_template_supplies_any_more(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Sales']);
        ContactListColumn::factory()->field($section, 'sales_region')->create(['vault_id' => $vault->id]);

        $column = new ContactListColumnsViewModel(user: $user, vault: $vault)->columns()[0];

        $this->assertFalse($column['isSupplied']);
        $this->assertSame($section->uuid, $column['section']);
    }

    #[Test]
    public function it_names_a_template_field_by_its_section(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']])['Work'];
        ContactListColumn::factory()->field($section, 'company')->create(['vault_id' => $vault->id, 'heading' => 'Employer']);

        $column = new ContactListColumnsViewModel(user: $user, vault: $vault)->columns()[0];

        $this->assertSame('Employer', $column['heading']);
        $this->assertSame('Company', $column['defaultHeading']);
        $this->assertSame('Work → Company', $column['sourceLabel']);
        $this->assertTrue($column['isSupplied']);
    }

    #[Test]
    public function it_tells_two_templates_of_the_same_name_apart(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->contactTemplateCarrying($user, 'Contact', ['Work' => ['company']]);
        $this->contactTemplateCarrying($user, 'Contact', ['Origins' => ['how_we_met']]);

        $groups = new ContactListColumnsViewModel(user: $user, vault: $vault)->additions();

        $this->assertEqualsCanonicalizing(['Company', 'How We Met'], array_column($groups[1]['items'], 'label'));
    }
}
