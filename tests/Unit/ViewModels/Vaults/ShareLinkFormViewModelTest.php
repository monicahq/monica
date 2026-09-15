<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ShareLinkFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_new_link_lets_every_section_through(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $pages = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact)->pages();

        $this->assertCount(2, $pages);
        $this->assertSame('Overview', $pages[0]['label']);
        $this->assertSame(['Facts', 'Doctors'], array_column($pages[0]['sections'], 'name'));

        foreach ($pages as $page) {
            foreach ($page['sections'] as $section) {
                $this->assertTrue($section['isIncluded']);
            }
        }
    }

    #[Test]
    public function an_existing_link_arrives_with_what_it_holds_back_unticked(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $doctors = $this->sectionNamed($contact, 'Doctors');
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'excluded_section_ids' => [$doctors->id],
        ]);

        $pages = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link)->pages();

        $sections = collect($pages[0]['sections'])->keyBy('name');

        $this->assertTrue($sections['Facts']['isIncluded']);
        $this->assertFalse($sections['Doctors']['isIncluded']);
    }

    #[Test]
    public function a_new_link_defaults_to_thirty_days(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $options = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact)->expiryOptions();

        $this->assertSame(['seven_days', 'thirty_days', 'three_months', 'never'], array_column($options, 'value'));
        $this->assertSame('thirty_days', collect($options)->firstWhere('isSelected', true)['value']);
    }

    #[Test]
    public function an_existing_end_date_is_offered_as_itself(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => now()->addDays(4)]);

        $options = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link)->expiryOptions();

        $this->assertSame('keep', $options[0]['value']);
        $this->assertTrue($options[0]['isSelected']);
        $this->assertStringContainsString(now()->addDays(4)->isoFormat('LL'), $options[0]['label']);
    }

    #[Test]
    public function a_link_that_never_ends_is_not_offered_a_date_to_keep(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => null]);

        $options = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link)->expiryOptions();

        $this->assertNotContains('keep', array_column($options, 'value'));
    }

    #[Test]
    public function it_knows_whether_it_is_making_a_link_or_changing_one(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['label' => 'For the family doctor']);

        $making = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact);
        $changing = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link);

        $this->assertFalse($making->isEditing());
        $this->assertSame('post', $making->method());
        $this->assertSame('Share Michael Scott', $making->title());
        $this->assertSame('Share', $making->breadcrumbLabel());

        $this->assertTrue($changing->isEditing());
        $this->assertSame('put', $changing->method());
        $this->assertStringContainsString('For the family doctor', $changing->title());
        $this->assertSame('For the family doctor', $changing->breadcrumbLabel());
    }

    #[Test]
    public function it_carries_what_the_link_already_says(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->withPassword()->create([
            'label' => 'For the family doctor',
            'recipient_hint' => 'Dr Ferrand',
            'includes_sensitive_fields' => true,
        ]);

        $viewModel = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link);

        $this->assertSame('For the family doctor', $viewModel->label());
        $this->assertSame('Dr Ferrand', $viewModel->recipientHint());
        $this->assertTrue($viewModel->includesSensitiveFields());
        $this->assertTrue($viewModel->isProtectedByAPassword());
    }

    #[Test]
    public function a_form_with_no_link_behind_it_says_nothing_is_set(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $viewModel = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertNull($viewModel->label());
        $this->assertNull($viewModel->recipientHint());
        $this->assertFalse($viewModel->includesSensitiveFields());
        $this->assertFalse($viewModel->isProtectedByAPassword());
    }

    #[Test]
    public function it_names_the_structure_version_the_page_is_laid_out_by(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $viewModel = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertStringContainsString('Someone in the family', $viewModel->templateLine());
        $this->assertStringContainsString('Someone in the family', $viewModel->sectionsLine());
        $this->assertSame(3, $viewModel->sectionCount());
    }

    #[Test]
    public function it_says_what_a_link_can_never_do(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $never = new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact)->neverLine();

        $this->assertStringContainsString('marked sensitive', $never);
        $this->assertStringContainsString('Michael', $never);
    }

    #[Test]
    public function a_record_with_no_photograph_has_no_address_for_one(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->assertNull(new ShareLinkFormViewModel(user: $user, vault: $contact->vault, contact: $contact)->avatarUrl());
    }

    private function sectionNamed(Contact $contact, string $name): StructureVersionSection
    {
        return $contact->structureVersion->assembly
            ->first(fn (StructureVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Someone in the family',
        ]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->page($version, 'Overview', ['Facts', 'Doctors']);
        $this->page($version, 'Work', ['Where they worked']);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }

    /** @param  list<string>  $sections */
    private function page(StructureVersion $version, string $label, array $sections): void
    {
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => mb_strtolower($label),
            'label' => $label,
        ]);

        foreach ($sections as $position => $name) {
            $section = Section::factory()->create(['account_id' => $version->account_id, 'name' => $name]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position + 1,
            ]);
        }
    }
}
