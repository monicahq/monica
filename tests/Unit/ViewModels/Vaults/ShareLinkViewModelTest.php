<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
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
    }

    #[Test]
    public function a_revoked_link_says_when_it_was_revoked_and_that_it_cannot_come_back(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $viewModel = $this->viewModel($user, $contact, $link);

        $this->assertFalse($viewModel->isLive());
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

        $this->assertContains('Dr Ferrand', $recap->pluck('value')->all());
    }

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

        $this->assertSame([], $viewModel->visits()->items());
    }

    #[Test]
    public function it_shows_twenty_visits_a_page_newest_first_and_still_counts_them_all(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 60]);

        for ($index = 1; $index <= 60; $index++) {
            ShareLinkVisit::factory()->for($link)->create([
                'account_id' => $link->account_id,
                'user_agent' => "Browser {$index}",
                'viewed_at' => now()->subMinutes($index),
            ]);
        }

        $firstPage = $this->viewModel($user, $contact, $link)->visits();

        $this->assertSame(60, $firstPage->total());
        $this->assertCount(20, $firstPage);
        $this->assertSame('Browser 1', $firstPage[0]['agent']);
        $this->assertSame('Browser 20', $firstPage[19]['agent']);

        $this->app['request']->merge(['page' => 2]);

        $secondPage = $this->viewModel($user, $contact, $link)->visits();

        $this->assertCount(20, $secondPage);
        $this->assertSame('Browser 21', $secondPage[0]['agent']);
        $this->assertSame('Browser 40', $secondPage[19]['agent']);

        $this->app['request']->merge(['page' => 3]);

        $thirdPage = $this->viewModel($user, $contact, $link)->visits();

        $this->assertCount(20, $thirdPage);
        $this->assertSame('Browser 41', $thirdPage[0]['agent']);
        $this->assertSame('Browser 60', $thirdPage[19]['agent']);
    }

    private function viewModel(User $user, Contact $contact, ShareLink $link): ShareLinkViewModel
    {
        return new ShareLinkViewModel(user: $user, vault: $contact->vault, contact: $contact, shareLink: $link);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'overview',
            'label' => 'Overview',
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Facts']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
        ]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
