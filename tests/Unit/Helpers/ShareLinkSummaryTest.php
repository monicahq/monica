<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ShareLinkSummary;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_when_the_whole_page_goes(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['excluded_section_ids' => []]);

        $this->assertSame('Every section of their page', ShareLinkSummary::showsLine($link, $contact));
    }

    #[Test]
    public function it_counts_the_sections_going_out(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create([
            'excluded_section_ids' => [$contact->templateVersion->assembly->first()->id],
        ]);

        $this->assertSame('2 of 3 sections', ShareLinkSummary::showsLine($link, $contact));
        $this->assertSame(2, ShareLinkSummary::sectionsShown($link, $contact));
        $this->assertSame(3, ShareLinkSummary::sectionsTotal($contact));
    }

    #[Test]
    public function it_names_the_sensitive_fields_only_when_they_are_let_through(): void
    {
        $contact = $this->contactWithThreeSections();
        $open = ShareLink::factory()->for($contact, 'shareable')->create(['includes_sensitive_fields' => true]);
        $closed = ShareLink::factory()->for($contact, 'shareable')->create(['includes_sensitive_fields' => false]);

        $this->assertStringContainsString('marked sensitive', ShareLinkSummary::showsLine($open, $contact));
        $this->assertStringNotContainsString('marked sensitive', ShareLinkSummary::showsLine($closed, $contact));
    }

    #[Test]
    public function it_lists_what_stays_here_whatever_the_form_said(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['excluded_section_ids' => []]);

        $held = ShareLinkSummary::heldBackLine($link, $contact);

        $this->assertStringContainsString('marked sensitive', $held);
        $this->assertStringContainsString('attached as a file', $held);
        $this->assertStringContainsString('everything technical', $held);
    }

    #[Test]
    public function it_counts_the_sections_staying_here(): void
    {
        $contact = $this->contactWithThreeSections();
        $ids = $contact->templateVersion->assembly->pluck('id')->all();

        $one = ShareLink::factory()->for($contact, 'shareable')->create(['excluded_section_ids' => [$ids[0]]]);
        $two = ShareLink::factory()->for($contact, 'shareable')->create(['excluded_section_ids' => [$ids[0], $ids[1]]]);

        $this->assertStringContainsString('One section', ShareLinkSummary::heldBackLine($one, $contact));
        $this->assertStringContainsString('2 sections', ShareLinkSummary::heldBackLine($two, $contact));
    }

    #[Test]
    public function a_revoked_link_says_when_it_was_revoked(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->revoked()->create();

        $this->assertStringStartsWith('Revoked ', ShareLinkSummary::endsLine($link));
    }

    #[Test]
    public function a_link_with_no_end_date_says_so(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => null]);

        $this->assertSame('Never, until you revoke it', ShareLinkSummary::endsLine($link));
    }

    #[Test]
    public function a_link_that_ran_out_says_when(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->expired()->create();

        $this->assertStringStartsWith('Expired ', ShareLinkSummary::endsLine($link));
    }

    #[Test]
    public function a_link_still_working_gives_the_date_and_how_long_that_is(): void
    {
        $contact = $this->contactWithThreeSections();
        $link = ShareLink::factory()->for($contact, 'shareable')->create(['expires_at' => now()->addDays(30)]);

        $ends = ShareLinkSummary::endsLine($link);

        $this->assertStringNotContainsString('Expired', $ends);
        $this->assertStringContainsString(now()->addDays(30)->isoFormat('LL'), $ends);
    }

    private function contactWithThreeSections(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => 'overview',
            'label' => 'Overview',
        ]);

        foreach (['Facts', 'Doctors', 'Where they worked'] as $position => $name) {
            $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            TemplateVersionSection::factory()->create([
                'template_version_id' => $version->id,
                'template_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position + 1,
            ]);
        }

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }
}
