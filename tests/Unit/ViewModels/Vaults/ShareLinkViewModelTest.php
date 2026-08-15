<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ShareLinkViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_working_link_says_it_is_live_and_that_the_address_is_shown_once(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();

        $viewModel = $this->viewModel($user, $contact, $link);

        $this->assertTrue($viewModel->isLive());
        $this->assertSame('The link is live', $viewModel->heading());
        $this->assertStringContainsString('only time it can be shown', $viewModel->intro());
    }

    #[Test]
    public function a_revoked_link_says_when_it_was_revoked_and_that_it_cannot_come_back(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $viewModel = $this->viewModel($user, $contact, $link);

        $this->assertFalse($viewModel->isLive());
        $this->assertSame('This link no longer works', $viewModel->heading());
        $this->assertStringContainsString('cannot be undone', $viewModel->intro());
    }

    #[Test]
    public function a_link_that_ran_out_says_it_stopped_on_its_own(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->expired()->create();

        $this->assertStringContainsString('on its own', $this->viewModel($user, $contact, $link)->intro());
    }

    #[Test]
    public function the_recap_says_everything_the_form_decided(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->withPassword()->create([
            'recipient_hint' => 'Dr Ferrand',
            'created_by_user_id' => $user->id,
        ]);

        $recap = collect($this->viewModel($user, $contact, $link)->recap())->keyBy('label');

        $this->assertSame('Every section of their page', $recap['Shows']['value']);
        $this->assertStringContainsString('marked sensitive', $recap['Held back']['value']);
        $this->assertSame('Asked for before the page opens', $recap['Password']['value']);
        $this->assertSame('Dr Ferrand', $recap['For']['value']);
        $this->assertStringContainsString('by you', $recap['Made']['value']);
    }

    #[Test]
    public function a_link_with_no_password_says_that_the_address_is_the_whole_of_it(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['recipient_hint' => null]);

        $recap = collect($this->viewModel($user, $contact, $link)->recap())->keyBy('label');

        $this->assertStringContainsString('Anybody holding the address', $recap['Password']['value']);
        $this->assertSame('Nobody in particular', $recap['For']['value']);
    }

    #[Test]
    public function a_link_whose_maker_has_gone_says_so_rather_than_naming_nobody(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['created_by_user_id' => null]);

        $recap = collect($this->viewModel($user, $contact, $link)->recap())->keyBy('label');

        $this->assertStringContainsString('since removed', $recap['Made']['value']);
    }

    #[Test]
    public function it_counts_the_openings_in_the_heading_of_the_visits(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $never = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 0]);
        $once = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 1]);
        $often = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 6]);

        $this->assertSame('Not opened yet', $this->viewModel($user, $contact, $never)->visitsHeader());
        $this->assertSame('Opened once', $this->viewModel($user, $contact, $once)->visitsHeader());
        $this->assertSame('Opened 6 times', $this->viewModel($user, $contact, $often)->visitsHeader());
    }

    /**
     * Nothing resolves an address to a country yet, so the address is what the row
     * has to say about where a visit came from.
     */
    #[Test]
    public function a_visit_reads_as_its_country_when_there_is_one_and_its_address_when_there_is_not(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();

        ShareLinkVisit::factory()->for($link)->create([
            'account_id' => $link->account_id,
            'ip_address' => '198.51.100.7',
            'country' => null,
            'user_agent' => 'Dunder Mifflin Explorer',
            'viewed_at' => now()->subMinute(),
        ]);
        ShareLinkVisit::factory()->for($link)->create([
            'account_id' => $link->account_id,
            'ip_address' => '203.0.113.9',
            'country' => 'fr',
            'user_agent' => null,
            'viewed_at' => now(),
        ]);

        $visits = $this->viewModel($user, $contact, $link)->visits();

        $this->assertSame('France', $visits[0]['where']);
        $this->assertSame('an unnamed browser', $visits[0]['agent']);
        $this->assertSame('198.51.100.7', $visits[1]['where']);
        $this->assertSame('Dunder Mifflin Explorer', $visits[1]['agent']);
    }

    #[Test]
    public function a_failed_password_is_marked_in_the_list(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();

        ShareLinkVisit::factory()->for($link)->passwordFailed()->create([
            'account_id' => $link->account_id,
            'viewed_at' => now(),
        ]);

        $this->assertTrue($this->viewModel($user, $contact, $link)->visits()[0]['failed']);
    }

    #[Test]
    public function a_link_nothing_has_opened_says_what_would_land_there(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create();

        $viewModel = $this->viewModel($user, $contact, $link);

        $this->assertSame([], $viewModel->visits());
        $this->assertStringContainsString('roughly where from', $viewModel->visitsEmptyLine());
    }

    private function viewModel(User $user, Contact $contact, ShareLink $link): ShareLinkViewModel
    {
        return new ShareLinkViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'overview',
            'label' => 'Overview',
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Facts']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
        ]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
