<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_tabs_are_the_overview_and_then_the_pages_of_the_version(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee', 'Sleep']]]);
        $contact = $this->contact($user, $version);

        $pages = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->pages();

        $this->assertSame(ContactViewModel::PAGE_OVERVIEW, $pages[0]['key']);
        $this->assertNull($pages[0]['count']);
        $this->assertTrue($pages[0]['isSelected']);

        $this->assertSame('health', $pages[1]['key']);
        $this->assertSame('Health', $pages[1]['label']);
        $this->assertSame(2, $pages[1]['count']);
        $this->assertFalse($pages[1]['isSelected']);
    }

    #[Test]
    public function the_page_the_key_names_is_the_one_that_is_open(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health');

        $this->assertFalse($viewModel->onOverview());
        $this->assertSame('Health', $viewModel->selectedPage()?->label);
    }

    #[Test]
    public function a_key_the_version_does_not_have_falls_back_to_the_overview(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        foreach ([null, 'overview', 'schrute-farms'] as $key) {
            $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: $key);

            $this->assertTrue($viewModel->onOverview());
            $this->assertSame([], $viewModel->sections());
        }
    }

    #[Test]
    public function the_open_page_carries_its_sections_and_the_fields_inside_them(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee'], 'Practitioners' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertCount(2, $sections);
        $this->assertSame('Ongoing', $sections[0]['name']);
        $this->assertSame('Knee', $sections[0]['fields'][0]['label']);
        $this->assertSame('Practitioners', $sections[1]['name']);
    }

    #[Test]
    public function a_section_marks_itself_in_a_hue_of_its_own(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee'], 'Practitioners' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertNotSame($sections[0]['iconClass'], $sections[1]['iconClass']);
    }

    #[Test]
    public function nothing_is_stored_against_a_field_yet(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertNull($sections[0]['fields'][0]['value']);
    }

    #[Test]
    public function it_says_which_template_and_which_version_of_it_the_page_renders_from(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Someone in the family');
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertSame('Someone in the family', $viewModel->templateName());
        $this->assertSame('v'.$version->version_number, $viewModel->templateVersionLabel());
    }

    #[Test]
    public function a_version_with_no_page_still_draws_the_overview(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $version->pages()->delete();
        $contact = $this->contact($user, $version->refresh());

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertCount(1, $viewModel->pages());
        $this->assertTrue($viewModel->onOverview());
    }

    #[Test]
    public function the_last_fact_beside_the_photograph_is_the_only_one_that_is_real(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version, ['last_activity_at' => now()->subDays(4)]);

        $facts = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->headerFacts();

        $this->assertSame('Not recorded', $facts[0]['value']);
        $this->assertStringContainsString('days ago', $facts[2]['value']);
    }

    #[Test]
    public function somebody_nobody_has_spoken_to_says_so(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $facts = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->headerFacts();

        $this->assertSame('never', $facts[2]['value']);
    }

    /**
     * A published version carrying the pages, sections and fields described.
     * It is assembled while the version is still a draft, because a published
     * one is frozen and refuses every one of these rows.
     *
     * @param  array<string, array<string, array<int, string>>>  $shape
     */
    private function version(User $user, array $shape, string $templateName = 'Basic contact'): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $templateName]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        /* The factory hands over the page the product supplies; the shape asked
           for here is the whole version rather than an addition to it. */
        $version->pages()->delete();

        foreach ($shape as $pageLabel => $sections) {
            $page = TemplateVersionPage::factory()->create([
                'template_version_id' => $version->id,
                'account_id' => $version->account_id,
                'key' => Str::slug($pageLabel),
                'label' => $pageLabel,
            ]);

            foreach ($sections as $sectionName => $fields) {
                $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
                $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

                foreach ($fields as $label) {
                    Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $label]);
                }

                TemplateVersionSection::factory()->create([
                    'template_version_id' => $version->id,
                    'template_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                ]);
            }
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contact(User $user, TemplateVersion $version, array $attributes = []): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            ...$attributes,
        ]);
    }
}
