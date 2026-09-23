<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Helpers\ContactListCells;
use App\Models\Contact;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListCellsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_values_of_a_repeated_field_in_the_order_they_were_written(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->templateVersion($user, 'Basic contact');
        $emails = $this->field($user, $version, 'Contact details', 'email', FieldTypeEnum::Email);
        $michael = $this->contact($vault, $version, 'Michael');

        $this->value($michael, $emails, 'michael.scott@dundermifflin.com', rowPosition: 2);
        $this->value($michael, $emails, 'worldsbestboss@gmail.com', rowPosition: 1);

        $cells = $this->cells($user, $vault, [ContactListColumnSourceEnum::Email], [$michael]);

        $this->assertSame([['worldsbestboss@gmail.com', 'michael.scott@dundermifflin.com']], $cells->for($michael));
    }

    #[Test]
    public function it_gathers_every_email_field_whatever_section_it_sits_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->templateVersion($user, 'Professional');
        $personal = $this->field($user, $version, 'Contact details', 'email', FieldTypeEnum::Email);
        $work = $this->field($user, $version, 'Work', 'work_email', FieldTypeEnum::Email);
        $jim = $this->contact($vault, $version, 'Jim');

        $this->value($jim, $personal, 'bigtuna@gmail.com');
        $this->value($jim, $work, 'jim.halpert@dundermifflin.com');

        $cells = $this->cells($user, $vault, [ContactListColumnSourceEnum::Email], [$jim]);

        $this->assertEqualsCanonicalizing(['bigtuna@gmail.com', 'jim.halpert@dundermifflin.com'], $cells->for($jim)[0]);
    }

    #[Test]
    public function it_shows_nothing_for_somebody_whose_template_does_not_carry_the_field(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $professional = $this->templateVersion($user, 'Professional');
        $basic = $this->templateVersion($user, 'Basic contact');
        $company = $this->field($user, $professional, 'Work', 'company', FieldTypeEnum::Text);
        $jan = $this->contact($vault, $professional, 'Jan');
        $dwight = $this->contact($vault, $basic, 'Dwight');

        $this->value($jan, $company, 'Serenity by Jan');

        $column = new ContactListColumn([
            'source' => ContactListColumnSourceEnum::Field,
            'section_id' => $company->sectionVersion->section_id,
            'field_key' => 'company',
        ]);
        $cells = new ContactListCells(user: $user, vault: $vault, columns: [$column], contacts: [$jan, $dwight]);

        $this->assertSame([['Serenity by Jan']], $cells->for($jan));
        $this->assertSame([[]], $cells->for($dwight));
    }

    #[Test]
    public function it_leaves_out_a_value_the_template_set_aside(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->templateVersion($user, 'Basic contact');
        $phones = $this->field($user, $version, 'Contact details', 'phone', FieldTypeEnum::Phone);
        $ryan = $this->contact($vault, $version, 'Ryan');

        FieldValue::factory()->detached()->create([
            'valuable_id' => $ryan->id,
            'field_id' => $phones->id,
            'value_string' => '+1 570 555 0144',
        ]);

        $cells = $this->cells($user, $vault, [ContactListColumnSourceEnum::Phone], [$ryan]);

        $this->assertSame([[]], $cells->for($ryan));
    }

    #[Test]
    public function it_reads_what_the_contact_itself_holds(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->templateVersion($user, 'Basic contact');
        $kevin = $this->contact($vault, $version, 'Kevin', [
            'nickname' => 'Kev',
            'created_at' => '2019-03-02 09:00:00',
        ]);

        $cells = $this->cells($user, $vault, [
            ContactListColumnSourceEnum::Nickname,
            ContactListColumnSourceEnum::Template,
            ContactListColumnSourceEnum::LastSpoke,
            ContactListColumnSourceEnum::Added,
        ], [$kevin]);

        $this->assertSame([['Kev'], ['Basic contact'], [], ['Mar 2, 2019']], $cells->for($kevin));
    }

    /**
     * @param  list<ContactListColumnSourceEnum>  $sources
     * @param  list<Contact>  $contacts
     */
    private function cells(User $user, Vault $vault, array $sources, array $contacts): ContactListCells
    {
        return new ContactListCells(
            user: $user,
            vault: $vault,
            columns: array_map(fn (ContactListColumnSourceEnum $source): ContactListColumn => new ContactListColumn(['source' => $source]), $sources),
            contacts: $contacts,
        );
    }

    private function templateVersion(User $user, string $name): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $name]);

        return StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
    }

    private function field(User $user, StructureVersion $version, string $sectionName, string $key, FieldTypeEnum $type): Field
    {
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'key' => $key, 'type' => $type]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return $field;
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(Vault $vault, StructureVersion $version, string $firstName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
            'first_name' => $firstName,
            ...$attributes,
        ]);
    }

    private function value(Contact $contact, Field $field, string $written, int $rowPosition = 1): void
    {
        $row = FieldValueRow::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => $rowPosition,
        ]);

        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_value_row_id' => $row->id,
            'value_string' => $written,
        ]);
    }
}
