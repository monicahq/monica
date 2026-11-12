<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\ViewModels\Settings\Account\Templates\StructureVersionEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_version_and_its_status(): void
    {
        $version = StructureVersion::factory()->published()->create(['version_number' => 2]);

        $entry = new StructureVersionEntry(version: $version->fresh());

        $this->assertSame('v2', $entry->label());
        $this->assertSame('success', $entry->tone());
        $this->assertFalse($entry->isDraft());
    }

    #[Test]
    public function it_marks_a_draft_so_the_timeline_can_draw_it_hollow(): void
    {
        $version = StructureVersion::factory()->create();

        $entry = new StructureVersionEntry(version: $version->fresh());

        $this->assertSame('warning', $entry->tone());
        $this->assertTrue($entry->isDraft());
    }

    #[Test]
    public function it_falls_back_when_a_version_carries_no_change_note(): void
    {
        $withNote = StructureVersion::factory()->create(['change_note' => 'Split the address out.']);
        StructureVersion::factory()->create(['change_note' => null]);

        $this->assertSame('Split the address out.', new StructureVersionEntry(version: $withNote->fresh())->changeNote());
    }

    #[Test]
    public function it_marks_a_section_the_previous_version_did_not_carry(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $carried = SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 1]);
        $bumped = SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 2]);

        $previous = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $previous->id,
            'section_version_id' => $carried->id,
        ]);

        $current = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $current->id,
            'section_version_id' => $bumped->id,
        ]);

        $pages = new StructureVersionEntry(version: $current->fresh(), previous: $previous->fresh())->pages();

        $this->assertSame('Address', $pages[0]['sections'][0]['name']);
        $this->assertSame('s2', $pages[0]['sections'][0]['version']);
        $this->assertTrue($pages[0]['sections'][0]['isNew']);
    }

    #[Test]
    public function the_first_version_marks_nothing_as_new(): void
    {
        $user = User::factory()->create();
        $version = $this->versionWith($user, sections: 1, fieldsPerSection: 1);

        $pages = new StructureVersionEntry(version: $version)->pages();

        $this->assertFalse($pages[0]['sections'][0]['isNew']);
    }

    #[Test]
    public function it_reads_the_pages_the_sections_and_the_fields(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Personal details']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Birthday']);

        $version = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $pages = new StructureVersionEntry(version: $version->fresh())->pages();

        $this->assertCount(1, $pages);
        $this->assertSame('Personal details', $pages[0]['sections'][0]['name']);
        $this->assertSame('Birthday', $pages[0]['sections'][0]['fields'][0]['label']);
    }

    #[Test]
    public function it_shows_the_title_the_structure_version_gave_a_section(): void
    {
        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Notes']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'title_override' => 'What we said',
        ]);

        $pages = new StructureVersionEntry(version: $version->fresh())->pages();

        $this->assertSame('What we said', $pages[0]['sections'][0]['name']);
    }

    private function versionWith(User $user, int $sections, int $fieldsPerSection): StructureVersion
    {
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        for ($i = 0; $i < $sections; $i++) {
            $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
            Field::factory()->count($fieldsPerSection)->create(['section_version_id' => $sectionVersion->id]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $i + 1,
            ]);
        }

        return $version->fresh();
    }
}
