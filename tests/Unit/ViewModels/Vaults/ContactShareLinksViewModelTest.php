<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactShareLinksViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactShareLinksViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_screen_after_the_person(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $viewModel = new ContactShareLinksViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertSame('Links to Michael’s page', $viewModel->title());
    }

    #[Test]
    public function it_reads_a_working_link_as_live(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create();

        $link = $this->only($user, $contact);

        $this->assertSame('Live', $link['state']);
        $this->assertSame('success', $link['stateTone']);
        $this->assertTrue($link['isLive']);
    }

    #[Test]
    public function it_reads_a_link_that_ran_out_as_expired(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->expired()->create();

        $link = $this->only($user, $contact);

        $this->assertSame('Expired', $link['state']);
        $this->assertSame('neutral', $link['stateTone']);
        $this->assertFalse($link['isLive']);
    }

    #[Test]
    public function it_reads_a_revoked_link_as_revoked(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->revoked()->create(['expires_at' => now()->addYear()]);

        $link = $this->only($user, $contact);

        $this->assertSame('Revoked', $link['state']);
        $this->assertSame('danger', $link['stateTone']);
    }

    #[Test]
    public function it_says_who_a_link_was_for_and_when_it_was_made(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['recipient_hint' => 'Pam Beesly']);

        $this->assertStringContainsString('Pam Beesly', $this->only($user, $contact)['meta']);
    }

    #[Test]
    public function a_link_for_nobody_in_particular_says_only_when_it_was_made(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['recipient_hint' => null]);

        $this->assertStringStartsWith('Made ', $this->only($user, $contact)['meta']);
    }

    #[Test]
    public function it_counts_the_openings(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 0]);

        $this->assertSame('Not opened', $this->only($user, $contact)['opened']);
        $this->assertSame('never opened', $this->only($user, $contact)['lastSeen']);
    }

    #[Test]
    public function it_says_once_rather_than_one_time(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 1, 'last_viewed_at' => now()->subHour()]);

        $this->assertSame('Opened once', $this->only($user, $contact)['opened']);
    }

    #[Test]
    public function the_confirmation_says_what_stops_and_what_stays(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['view_count' => 6, 'last_viewed_at' => now()->subDay()]);

        $line = $this->only($user, $contact)['revokeLine'];

        $this->assertStringContainsString('6 times', $line);
        $this->assertStringContainsString('never brought back', $line);
    }

    #[Test]
    public function it_leaves_out_a_link_onto_somebody_else(): void
    {
        $user = User::factory()->create();
        $michael = $this->contactOf($user);
        $dwight = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $michael->vault_id,
            'template_version_id' => $michael->template_version_id,
            'first_name' => 'Dwight',
        ]);
        ShareLink::factory()->for($dwight, 'shareable')->create();

        $viewModel = new ContactShareLinksViewModel(user: $user, vault: $michael->vault, contact: $michael);

        $this->assertSame([], $viewModel->links());
    }

    /** @return array<string, mixed> */
    private function only(User $user, Contact $contact): array
    {
        $links = new ContactShareLinksViewModel(user: $user, vault: $contact->vault, contact: $contact)->links();

        $this->assertCount(1, $links);

        return $links[0];
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
