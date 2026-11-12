<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_version_newest_first(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['v2', 'v1']);
    }
}
