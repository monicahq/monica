<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $template = Template::factory()->create();
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $template->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_reads_by_its_translation_key_when_the_product_supplied_it(): void
    {
        $template = Template::factory()->supplied()->create();

        $this->assertNull($template->getRawOriginal('name'));
    }
}
