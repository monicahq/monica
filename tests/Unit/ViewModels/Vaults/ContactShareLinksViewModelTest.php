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
use App\ViewModels\Vaults\ContactShareLinksViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactShareLinksViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_working_link_as_live(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create();

        $link = $this->only($user, $contact);

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
    public function it_says_when_a_link_was_made_on_the_day_of_the_reader(): void
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $contact = $this->contactOf($user);
        ShareLink::factory()->for($contact, 'shareable')->create(['recipient_hint' => null, 'created_at' => '2026-01-15 02:00:00']);

        $this->assertSame('Made January 14, 2026', $this->only($user, $contact)['meta']);
    }

    #[Test]
    public function it_leaves_out_a_link_onto_somebody_else(): void
    {
        $user = User::factory()->create();
        $michael = $this->contactOf($user);
        $dwight = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $michael->vault_id,
            'structure_version_id' => $michael->structure_version_id,
            'first_name' => 'Dwight',
        ]);
        ShareLink::factory()->for($dwight, 'shareable')->create();

        $viewModel = new ContactShareLinksViewModel(user: $user, vault: $michael->vault, contact: $michael);

        $this->assertSame([], $viewModel->links()->items());
    }

    #[Test]
    public function it_shows_twenty_links_a_page_newest_first_with_the_revoked_ones_kept(): void
    {
        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        for ($index = 1; $index <= 25; $index++) {
            $factory = ShareLink::factory()->for($contact, 'shareable');

            if ($index % 5 === 0) {
                $factory = $factory->revoked();
            }

            $factory->create(['label' => "Link {$index}", 'created_at' => now()->subMinutes($index)]);
        }

        $firstPage = new ContactShareLinksViewModel(user: $user, vault: $contact->vault, contact: $contact)->links();

        $this->assertSame(25, $firstPage->total());
        $this->assertCount(20, $firstPage);
        $this->assertSame('Link 1', $firstPage[0]['label']);
        $this->assertSame('Link 20', $firstPage[19]['label']);

        $this->app['request']->merge(['page' => 2]);

        $secondPage = new ContactShareLinksViewModel(user: $user, vault: $contact->vault, contact: $contact)->links();

        $this->assertCount(5, $secondPage);
        $this->assertSame('Link 21', $secondPage[0]['label']);
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
