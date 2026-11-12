<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $structure = Template::factory()->create()->structure;
        StructureVersion::factory()->create(['structure_id' => $structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create(['structure_id' => $structure->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $structure->versions->pluck('version_number')->all());
    }
}
