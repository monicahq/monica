<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListTemplateVersions;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListTemplateVersionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_versions_of_the_template_it_was_given(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(ListTemplateVersions::class, ['template' => $template->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee((string) $version->version);
    }
}
