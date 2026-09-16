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

    #[Test]
    public function it_reports_a_template_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $theirs = Template::factory()->create();

        EricaServer::actingAs($user)->tool(ListTemplateVersions::class, ['template' => $theirs->uuid])
            ->assertHasErrors(['That template does not exist.']);
    }

    #[Test]
    public function it_refuses_without_a_template(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(ListTemplateVersions::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.versions', app(ListTemplateVersions::class)->mirrors());
    }
}
