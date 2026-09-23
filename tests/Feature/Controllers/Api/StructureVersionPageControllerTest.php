<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Account;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionPageControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => ['type', 'id', 'attributes' => ['key', 'label', 'position', 'sections']],
    ];

    #[Test]
    public function it_adds_a_page_to_a_draft(): void
    {
        Queue::fake();
        [$user, $template, $version] = $this->draft();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/pages", [
            'label' => 'Medical',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.label', 'Medical');
        $response->assertJsonPath('data.attributes.key', 'medical');
        $response->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_keeps_the_key_a_page_was_settled_when_it_is_renamed(): void
    {
        Queue::fake();
        [$user, $template, $version] = $this->draft();
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'key' => 'medical',
            'label' => 'Medical',
            'position' => 2,
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}", [
            'label' => 'Health',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.label', 'Health')
            ->assertJsonPath('data.attributes.key', 'medical');
    }

    #[Test]
    public function it_refuses_a_label_another_page_of_the_version_already_reads(): void
    {
        Queue::fake();
        [$user, $template, $version] = $this->draft();
        StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'key' => 'medical',
            'label' => 'Medical',
            'position' => 2,
        ]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/pages", ['label' => 'Medical'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label']);
    }

    #[Test]
    public function it_deletes_a_page_that_holds_nothing(): void
    {
        Queue::fake();
        [$user, $template, $version] = $this->draft();
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'position' => 2,
        ]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($page);
    }

    #[Test]
    public function it_returns_not_found_for_a_page_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $page = $version->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/pages", ['label' => 'Medical'])->assertNotFound();
        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}", ['label' => 'Health'])->assertNotFound();
        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}")->assertNotFound();

        $this->assertModelExists($page);
    }

    #[Test]
    public function it_returns_not_found_for_a_page_that_belongs_to_another_version(): void
    {
        Queue::fake();
        [$user, $template, $version] = $this->draft();
        $other = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);
        $page = $other->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}", ['label' => 'Health'])
            ->assertNotFound();
    }

    /** @return array{User, Template, StructureVersion} */
    private function draft(): array
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        return [$user, $template, $version];
    }
}
