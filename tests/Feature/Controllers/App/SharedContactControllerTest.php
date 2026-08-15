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
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
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
            ->assertSee('Scranton')
            ->assertSee('Read only');
    }

    #[Test]
    public function it_writes_the_visit_down_and_counts_it(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN]))->assertStatus(200);

        $link->refresh();

        $this->assertSame(1, $link->view_count);
        $this->assertNotNull($link->last_viewed_at);
        $this->assertDatabaseHas('share_link_visits', [
            'share_link_id' => $link->id,
            'password_failed' => false,
        ]);
    }

    #[Test]
    public function it_says_the_same_thing_about_an_unknown_token(): void
    {
        $this->get(route('share.show', ['token' => str_repeat('a', 64)]))
            ->assertStatus(200)
            ->assertSee('This link no longer works');
    }

    #[Test]
    public function it_says_the_same_thing_about_an_expired_link(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['expires_at' => now()->subDay()]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('This link no longer works')
            ->assertDontSee('Michael Scott');
    }

    #[Test]
    public function it_says_the_same_thing_about_a_revoked_link(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['revoked_at' => now()->subHour()]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('This link no longer works')
            ->assertDontSee('Michael Scott');
    }

    #[Test]
    public function it_refuses_an_address_that_is_not_shaped_like_a_token(): void
    {
        $this->get('/s/nope')->assertNotFound();
    }

    #[Test]
    public function it_asks_for_the_password_before_it_opens_anything(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('This page asks for a password')
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
            ->assertSee('That password does not open this link.')
            ->assertDontSee('Michael Scott');

        $this->assertDatabaseHas('share_link_visits', [
            'share_link_id' => $link->id,
            'password_failed' => true,
        ]);
        $this->assertSame(0, $link->refresh()->view_count);
    }

    /**
     * A password the owner has since changed must not go on writing a failed
     * visit on every click, so the browsing session forgets it.
     */
    #[Test]
    public function it_forgets_a_password_that_stopped_working(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['password_hash' => Hash::make('bears beets battlestar')]);

        $this->post(route('share.password.create', ['token' => self::TOKEN]), ['password' => 'wrong one entirely']);
        $this->get(route('share.show', ['token' => self::TOKEN]));
        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('This page asks for a password')
            ->assertDontSee('That password does not open this link.');

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
    public function it_leaves_out_a_section_the_link_holds_back(): void
    {
        $contact = $this->sharedContact();
        $doctors = $this->sectionNamed($contact, 'Doctors');
        $this->linkOnto($contact, ['excluded_section_ids' => [$doctors->id]]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Facts')
            ->assertDontSee('Doctors')
            ->assertDontSee('Dr Ferrand');
    }

    #[Test]
    public function it_leaves_out_a_field_marked_sensitive(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN, 'page' => 'work']))
            ->assertStatus(200)
            ->assertSee('Dunder Mifflin')
            ->assertDontSee('Social security number')
            ->assertDontSee('123-45-6789');
    }

    #[Test]
    public function it_shows_a_field_marked_sensitive_when_the_link_says_so(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['includes_sensitive_fields' => true]);

        $this->get(route('share.show', ['token' => self::TOKEN, 'page' => 'work']))
            ->assertStatus(200)
            ->assertSee('123-45-6789');
    }

    #[Test]
    public function it_draws_a_tab_for_each_page_that_still_has_something_on_it(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Overview')
            ->assertSee('Work');
    }

    #[Test]
    public function a_page_whose_every_section_is_held_back_is_not_a_tab(): void
    {
        $contact = $this->sharedContact();
        $work = $this->sectionNamed($contact, 'Where they worked');
        $this->linkOnto($contact, ['excluded_section_ids' => [$work->id]]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertDontSee('Work')
            ->assertSee('Facts');
    }

    #[Test]
    public function one_page_left_means_no_tab_list(): void
    {
        $contact = $this->sharedContact();
        $facts = $this->sectionNamed($contact, 'Facts');
        $doctors = $this->sectionNamed($contact, 'Doctors');
        $this->linkOnto($contact, ['excluded_section_ids' => [$facts->id, $doctors->id]]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Where they worked')
            ->assertDontSee('role="tablist"', false);
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
    public function it_falls_back_to_a_shared_page_when_the_address_names_one_that_is_held_back(): void
    {
        $contact = $this->sharedContact();
        $work = $this->sectionNamed($contact, 'Where they worked');
        $this->linkOnto($contact, ['excluded_section_ids' => [$work->id]]);

        $this->get(route('share.show', ['token' => self::TOKEN, 'page' => 'work']))
            ->assertStatus(200)
            ->assertSee('Facts');
    }

    #[Test]
    public function it_says_who_shared_it(): void
    {
        $contact = $this->sharedContact();
        $this->linkOnto($contact, ['created_by_user_id' => $contact->account->user->id]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('Shared with you by');
    }

    #[Test]
    public function it_has_nothing_to_say_about_a_link_onto_a_whole_vault(): void
    {
        $contact = $this->sharedContact();
        ShareLink::factory()->forVault($contact->vault)->create([
            'token' => hash('sha256', self::TOKEN),
        ]);

        $this->get(route('share.show', ['token' => self::TOKEN]))
            ->assertStatus(200)
            ->assertSee('there is no page for that yet');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkOnto(Contact $contact, array $attributes = []): ShareLink
    {
        return ShareLink::factory()->for($contact, 'shareable')->create([
            'token' => hash('sha256', self::TOKEN),
            'label' => 'For the family doctor',
            ...$attributes,
        ]);
    }

    /**
     * Michael, laid out over two pages, with something filled in on each and one
     * field the template marks sensitive.
     */
    private function sharedContact(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

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
            'template_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->answer($contact, 'Lives', 'Scranton');
        $this->answer($contact, 'Doctor', 'Dr Ferrand');
        $this->answer($contact, 'Employer', 'Dunder Mifflin');
        $this->answer($contact, 'Social security number', '123-45-6789');

        return $contact;
    }

    /**
     * @param  array<string, array<int, array{0: string, 1: bool}>>  $sections
     */
    private function page(TemplateVersion $version, string $label, array $sections): TemplateVersionPage
    {
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
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

            TemplateVersionSection::factory()->create([
                'template_version_id' => $version->id,
                'template_version_page_id' => $page->id,
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

    private function sectionNamed(Contact $contact, string $name): TemplateVersionSection
    {
        return $contact->templateVersion->assembly
            ->first(fn (TemplateVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }
}
