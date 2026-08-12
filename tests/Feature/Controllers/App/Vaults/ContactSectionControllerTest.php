<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_one_block_for_editing(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="fields['.$knee->id.']"', false)
            ->assertSee('Save this section');
    }

    #[Test]
    public function it_leaves_every_other_block_reading(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee', 'Diet' => 'Allergies']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $allergies = Field::query()->where('label', 'Allergies')->firstOrFail();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="fields['.$knee->id.']"', false)
            ->assertDontSee('name="fields['.$allergies->id.']"', false)
            ->assertSee('Allergies');
    }

    /**
     * A field the template marks as highlighted is drawn beside the photograph
     * as well, so a save that replaced only the panel would leave the header
     * stating the answer it used to hold.
     */
    #[Test]
    public function saving_a_block_reaches_the_facts_beside_the_photograph_too(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $row = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $row]))
            ->assertStatus(200)
            ->assertSee('x-target="section-'.$row->id.' contact-facts"', false)
            ->assertSee('id="contact-facts"', false);
    }

    #[Test]
    public function it_saves_one_block(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', [
                    'vault' => $vault,
                    'contact' => $michael,
                    'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
                ]),
                ['fields' => [$knee->id => 'Twisted it skiing']],
            )
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $michael->id,
            'field_id' => $knee->id,
            'value_string' => 'Twisted it skiing',
        ]);
    }

    #[Test]
    public function it_writes_nothing_in_a_block_it_was_not_asked_about(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee', 'Diet' => 'Allergies']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $allergies = Field::query()->where('label', 'Allergies')->firstOrFail();

        $this->actingAs($user)->put(
            route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]),
            ['fields' => [$knee->id => 'Twisted it skiing', $allergies->id => 'Peanuts']],
        );

        $this->assertDatabaseHas('field_values', ['field_id' => $knee->id, 'value_string' => 'Twisted it skiing']);
        $this->assertDatabaseMissing('field_values', ['field_id' => $allergies->id]);
    }

    #[Test]
    public function it_sends_a_block_back_open_when_a_required_field_is_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $knee->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $knee->update(['is_required' => true]);

        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => [$knee->id => '']],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertSessionHasErrors('fields.'.$knee->id);

        $this->assertSame(0, FieldValue::query()->count());
    }

    /**
     * The placeholder year is what makes a birthday with no year storable, and
     * it must never reach a screen: not as text on the page, and not sitting in
     * the date control either, where it would read as a fact somebody entered.
     */
    #[Test]
    public function it_never_puts_the_placeholder_year_on_the_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Dates' => 'Birthday']);
        $michael->update(['template_version_id' => $version->id]);
        $birthday = Field::query()->where('label', 'Birthday')->firstOrFail();
        $birthday->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $birthday->update(['type' => FieldTypeEnum::RecurringDate]);

        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
            [
                'fields' => [$birthday->id => '1964-03-15'],
                'year_is_unknown' => [$birthday->id => '1'],
            ],
        );

        $stored = FieldValue::query()->where('field_id', $birthday->id)->firstOrFail();

        $this->assertTrue($stored->year_is_unknown);
        $this->assertSame(FieldValue::PLACEHOLDER_YEAR, (int) $stored->value_date->format('Y'));

        $reading = (string) $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->getContent();

        $editing = (string) $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->getContent();

        $this->assertStringNotContainsString((string) FieldValue::PLACEHOLDER_YEAR, $reading);
        $this->assertStringNotContainsString((string) FieldValue::PLACEHOLDER_YEAR, $editing);
    }

    #[Test]
    public function it_does_not_find_a_block_the_structure_does_not_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);

        $elsewhere = $this->versionWithSections($user, 'Work', ['Desk' => 'Floor']);
        $theirs = $this->sectionNamed($elsewhere, 'Desk');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $theirs]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $theirs]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_block_of_somebody_kept_in_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user, 'Someone at work');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $elsewhere, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $elsewhere, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_block_of_somebody_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirs = $this->vaultReadyForContacts($stranger);
        $michael = $this->contact($theirs, 'Michael', 'Scott');

        $version = $this->versionWithSections($stranger, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $theirs, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $theirs, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    /**
     * A published version carrying one page and, on it, one section per name
     * given with one field inside each. It is assembled while the version is
     * still a draft, because a published one is frozen and refuses every one of
     * these rows.
     *
     * @param  array<string, string>  $sections  the field label, keyed by the section name
     */
    private function versionWithSections(User $user, string $pageLabel, array $sections): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($pageLabel),
            'label' => $pageLabel,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fieldLabel) {
            $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

            TemplateVersionSection::factory()->create([
                'template_version_id' => $version->id,
                'template_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    private function sectionNamed(TemplateVersion $version, string $name): TemplateVersionSection
    {
        return $version->assembly
            ->first(fn (TemplateVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contact(Vault $vault, string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'template_version_id' => $vault->defaultTemplates()->sole()->template_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            ...$attributes,
        ]);
    }

    private function vaultReadyForContacts(User $user, string $name = 'Basic contact'): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, $name);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $template->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
