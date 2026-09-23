<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_the_sections_placed_on_it_in_order(): void
    {
        $version = StructureVersion::factory()->create();
        $page = $version->pages()->firstOrFail();

        $second = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'position' => 2,
        ]);
        $first = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'position' => 1,
        ]);

        $this->assertEquals([$first->id, $second->id], $page->assembly->pluck('id')->all());
    }

    #[Test]
    public function it_reads_by_its_translation_key_until_somebody_names_it(): void
    {
        $supplied = StructureVersion::factory()->create()->pages()->firstOrFail();
        $named = StructureVersionPage::factory()->create(['label' => 'Health']);

        $this->assertEquals('General', $supplied->label);
        $this->assertEquals('Health', $named->label);
    }

    #[Test]
    public function it_is_reachable_through_the_template_it_sits_on(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $page = StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);
        $elsewhere = StructureVersionPage::factory()->create();

        $this->assertTrue($page->isReachableThrough($user, $template));
        $this->assertFalse($elsewhere->isReachableThrough($user, $template));
    }

    #[Test]
    public function it_refuses_to_be_added_to_a_published_structure_version(): void
    {
        $version = StructureVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);
    }

    #[Test]
    public function it_refuses_to_be_renamed_once_the_structure_version_is_published(): void
    {
        $version = StructureVersion::factory()->create();
        $page = $version->pages()->firstOrFail();
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $page->label = 'Health';
        $page->save();
    }

    #[Test]
    public function it_refuses_to_be_taken_off_a_published_structure_version(): void
    {
        $version = StructureVersion::factory()->create();
        $page = $version->pages()->firstOrFail();
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $page->delete();
    }
}
