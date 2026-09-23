<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
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
        $response->assertSee('Versions of Basic contact');
        $response->assertSeeInOrder(['v2', 'v1']);
        $response->assertSee('Draft');
        $response->assertSee('Published');
        $response->assertSee('based on v1');
        $response->assertSee('the first version');
    }

    #[Test]
    public function it_changes_nothing_it_shows(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $before = $version->updated_at;

        $this->actingAs($user)->get(route('settings.templateVersions.index', $template))->assertStatus(200);

        $this->assertDatabaseHas('structure_versions', [
            'id' => $version->id,
            'status' => VersionStatusEnum::Published->value,
            'updated_at' => $before,
        ]);
    }
}
