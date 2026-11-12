<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ShareLinkSummary;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_the_sections_going_out(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'excluded_section_ids' => [$contact->structureVersion->assembly->first()->id],
        ]);

        $this->assertSame(2, ShareLinkSummary::sectionsShown($link, $contact));
        $this->assertSame(3, ShareLinkSummary::sectionsTotal($contact));
    }

    #[Test]
    public function a_link_still_working_gives_the_date_and_how_long_that_is(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => now()->addDays(30)]);

        $ends = ShareLinkSummary::endsLine($link);

        $this->assertStringContainsString(now()->addDays(30)->isoFormat('LL'), $ends);
    }

    private function contactWithThreeSections(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'overview',
            'label' => 'Overview',
        ]);

        foreach (['Facts', 'Doctors', 'Where they worked'] as $position => $name) {
            $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position + 1,
            ]);
        }

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
