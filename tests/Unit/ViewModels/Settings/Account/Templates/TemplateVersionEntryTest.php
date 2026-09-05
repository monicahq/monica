<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\TemplateVersionEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_version_and_its_status(): void
    {
        $version = TemplateVersion::factory()->published()->create(['version_number' => 2]);

        $entry = new TemplateVersionEntry(version: $version->fresh());

        $this->assertSame('v2', $entry->label());
        $this->assertSame('Published', $entry->statusLabel());
        $this->assertSame('success', $entry->tone());
        $this->assertFalse($entry->isDraft());
    }

    #[Test]
    public function it_marks_a_draft_so_the_timeline_can_draw_it_hollow(): void
    {
        $version = TemplateVersion::factory()->create();

        $entry = new TemplateVersionEntry(version: $version->fresh());

        $this->assertSame('Draft', $entry->statusLabel());
        $this->assertSame('warning', $entry->tone());
        $this->assertTrue($entry->isDraft());
    }

    #[Test]
    public function it_reads_a_retired_version(): void
    {
        $version = TemplateVersion::factory()->retired()->create();

        $entry = new TemplateVersionEntry(version: $version->fresh());

        $this->assertSame('Retired', $entry->statusLabel());
        $this->assertSame('neutral', $entry->tone());
    }

    #[Test]
    public function it_dates_a_published_version_absolutely_and_a_draft_by_recency(): void
    {
        $published = TemplateVersion::factory()->create([
            'status' => 'published',
            'published_at' => '2026-03-14 09:00:00',
        ]);
        $draft = TemplateVersion::factory()->create();

        $this->assertSame('Published March 14, 2026', new TemplateVersionEntry(version: $published->fresh())->dateLine());
        $this->assertStringStartsWith('Saved ', new TemplateVersionEntry(version: $draft->fresh())->dateLine());
    }

    #[Test]
    public function it_says_which_version_it_was_based_on(): void
    {
        $template = Template::factory()->create();
        $first = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->assertSame('the first version', new TemplateVersionEntry(version: $first->fresh())->basedOn());
        $this->assertSame('based on v1', new TemplateVersionEntry(version: $second->fresh(), previous: $first->fresh())->basedOn());
    }

    #[Test]
    public function it_falls_back_when_a_version_carries_no_change_note(): void
    {
        $withNote = TemplateVersion::factory()->create(['change_note' => 'Split the address out.']);
        $without = TemplateVersion::factory()->create(['change_note' => null]);

        $this->assertSame('Split the address out.', new TemplateVersionEntry(version: $withNote->fresh())->changeNote());
        $this->assertSame(
            'No change note yet. You write one when you publish this version.',
            new TemplateVersionEntry(version: $without->fresh())->changeNote(),
        );
    }

    #[Test]
    public function it_counts_what_the_version_holds(): void
    {
        $user = User::factory()->create();
        $version = $this->versionWith($user, sections: 2, fieldsPerSection: 3);

        $entry = new TemplateVersionEntry(version: $version);

        $this->assertSame('1 page · 2 sections · 6 fields', $entry->counts());
    }

    #[Test]
    public function the_first_version_is_where_the_template_starts(): void
    {
        $user = User::factory()->create();
        $version = $this->versionWith($user, sections: 1, fieldsPerSection: 1);

        $this->assertSame('Where the template starts', new TemplateVersionEntry(version: $version)->summary());
    }

    #[Test]
    public function it_summarises_what_a_version_added_over_the_one_it_came_from(): void
    {
        $user = User::factory()->create();
        $previous = $this->versionWith($user, sections: 1, fieldsPerSection: 2);
        $current = $this->versionWith($user, sections: 3, fieldsPerSection: 2);

        $entry = new TemplateVersionEntry(version: $current, previous: $previous);

        $this->assertSame('2 sections added, 4 fields added', $entry->summary());
    }

    #[Test]
    public function it_says_when_a_section_moved_to_a_new_version_without_anything_being_added(): void
    {
        $user = User::factory()->create();
        $previous = $this->versionWith($user, sections: 1, fieldsPerSection: 2);
        $current = $this->versionWith($user, sections: 1, fieldsPerSection: 2);

        $entry = new TemplateVersionEntry(version: $current, previous: $previous);

        $this->assertSame('1 section at a new version', $entry->summary());
    }

    #[Test]
    public function it_marks_a_section_the_previous_version_did_not_carry(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $carried = SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 1]);
        $bumped = SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 2]);

        $previous = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $previous->id,
            'section_version_id' => $carried->id,
        ]);

        $current = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $current->id,
            'section_version_id' => $bumped->id,
        ]);

        $pages = new TemplateVersionEntry(version: $current->fresh(), previous: $previous->fresh())->pages();

        $this->assertSame('Address', $pages[0]['sections'][0]['name']);
        $this->assertSame('s2', $pages[0]['sections'][0]['version']);
        $this->assertTrue($pages[0]['sections'][0]['isNew']);
    }

    #[Test]
    public function the_first_version_marks_nothing_as_new(): void
    {
        $user = User::factory()->create();
        $version = $this->versionWith($user, sections: 1, fieldsPerSection: 1);

        $pages = new TemplateVersionEntry(version: $version)->pages();

        $this->assertFalse($pages[0]['sections'][0]['isNew']);
    }

    #[Test]
    public function it_reads_the_pages_the_sections_and_the_fields(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Personal details']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Birthday']);

        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $pages = new TemplateVersionEntry(version: $version->fresh())->pages();

        $this->assertCount(1, $pages);
        $this->assertSame('Page 1', $pages[0]['position']);
        $this->assertSame('1 section · 1 field', $pages[0]['meta']);
        $this->assertSame('Personal details', $pages[0]['sections'][0]['name']);
        $this->assertSame('1 field', $pages[0]['sections'][0]['fieldLabel']);
        $this->assertSame('Birthday', $pages[0]['sections'][0]['fields'][0]['label']);
    }

    #[Test]
    public function it_shows_the_title_the_template_version_gave_a_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Notes']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'title_override' => 'What we said',
        ]);

        $pages = new TemplateVersionEntry(version: $version->fresh())->pages();

        $this->assertSame('What we said', $pages[0]['sections'][0]['name']);
    }

    private function versionWith(User $user, int $sections, int $fieldsPerSection): TemplateVersion
    {
        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);

        for ($i = 0; $i < $sections; $i++) {
            $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
            Field::factory()->count($fieldsPerSection)->create(['section_version_id' => $sectionVersion->id]);

            TemplateVersionSection::factory()->create([
                'template_version_id' => $version->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $i + 1,
            ]);
        }

        return $version->fresh();
    }
}
