<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string TOKEN = 'ScottsTotsScottsTotsScottsTotsScottsTotsScottsTotsScottsTots1234';

    #[Test]
    public function it_opens_a_page_for_somebody_with_no_account(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Facts')
            ->assertSee('Scranton');
    }

    #[Test]
    public function it_shows_an_address_on_the_lines_of_its_country(): void
    {
        $contact = $this->sharedContact();
        $field = Field::query()->where('label', 'Lives')->firstOrFail();
        $field->update(['type' => FieldTypeEnum::Address]);
        FieldValue::query()->where('field_id', $field->id)->firstOrFail()->update([
            'value_string' => null,
            'value_json' => ['street' => '1725 Slough Avenue', 'city' => 'Scranton', 'region' => 'PA', 'postal_code' => '18505', 'country' => 'United States'],
        ]);
        $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSeeInOrder(['1725 Slough Avenue', 'Scranton, PA 18505', 'United States']);
    }

    #[Test]
    public function it_says_the_same_thing_about_an_unknown_token(): void
    {
        $this->get(route('share.show', ['token' => str_repeat('a', 64)]))
            ->assertStatus(200);
    }

    #[Test]
    public function it_asks_for_the_password_before_it_opens_anything(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertDontSee('Michael Scott');

        $this->assertSame(0, $link->refresh()->view_count);
        $this->assertDatabaseCount('share_link_visits', 0);
    }

    #[Test]
    public function it_opens_the_page_once_the_password_is_right(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->post(route('share.password.create', ['token' => self::TOKEN]), [
            'password' => 'bears beets battlestar',
        ])->assertRedirect(route('share.show', ['token' => self::TOKEN]));

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Michael Scott');
    }

    #[Test]
    public function it_writes_a_wrong_password_down_before_refusing_it(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->post(route('share.password.create', ['token' => self::TOKEN]), [
            'password' => 'wrong one entirely',
        ])->assertRedirect(route('share.show', ['token' => self::TOKEN]));

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertDontSee('Michael Scott');

        $this->assertDatabaseHas('share_link_visits', [
            'share_link_id' => $link->id,
            'password_failed' => true,
        ]);
        $this->assertSame(0, $link->refresh()->view_count);
    }

    #[Test]
    public function it_forgets_a_password_that_stopped_working(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->post(route('share.password.create', ['token' => self::TOKEN]), ['password' => 'wrong one entirely']);
        $this->get(route('share.show', ['token' => self::TOKEN]));
        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200);

        $this->assertSame(1, $link->visits()->where('password_failed', true)->count());
    }

    #[Test]
    public function it_refuses_a_password_form_with_nothing_in_it(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->post(route('share.password.create', ['token' => self::TOKEN]), ['password' => ''])
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function it_opens_the_page_named_in_the_address(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN, 'page' => 'work']))
            ->assertStatus(200)
            ->assertSee('Where they worked')
            ->assertSee('Dunder Mifflin');
    }

    #[Test]
    public function it_has_nothing_to_say_about_a_link_onto_a_whole_vault(): void
    {
        $contact = $this->sharedContact();
        ShareLink::factory()->forVault($contact->vault)->create([
            'token' => hash('sha256', self::TOKEN),
        ]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200);
    }

    /** @param  array<string, mixed>  $attributes */
    private function linkOnto(Contact $contact, array $attributes = []): ShareLink
    {
        return ShareLink::factory()->for($contact, 'shareable')->create([
            'token' => hash('sha256', self::TOKEN),
            'label' => 'For the family doctor',
            ...$attributes,
        ]);
    }

    private function sharedContact(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->page($version, 'Overview', [
            'Facts' => [['Lives', false]],
            'Doctors' => [['Doctor', false]],
        ]);
        $this->page($version, 'Work', [
            'Where they worked' => [['Employer', false], ['Social security number', true]],
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);
        $version->refresh();

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->answer($contact, 'Lives', 'Scranton');
        $this->answer($contact, 'Doctor', 'Dr Ferrand');
        $this->answer($contact, 'Employer', 'Dunder Mifflin');
        $this->answer($contact, 'Social security number', '123-45-6789');

        return $contact;
    }

    /** @param  array<string, array<int, array{0: string, 1: bool}>>  $sections */
    private function page(StructureVersion $version, string $label, array $sections): StructureVersionPage
    {
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($label),
            'label' => $label,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fields) {
            $section = Section::factory()->create(['account_id' => $version->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            foreach ($fields as [$fieldLabel, $isSensitive]) {
                Field::factory()->create([
                    'section_version_id' => $sectionVersion->id,
                    'label' => $fieldLabel,
                    'type' => FieldTypeEnum::Text,
                    'is_sensitive' => $isSensitive,
                ]);
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }

        return $page;
    }

    private function answer(Contact $contact, string $label, string $value): FieldValue
    {
        $field = Field::query()->where('label', $label)->firstOrFail();

        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }
}
