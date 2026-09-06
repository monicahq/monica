<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VersionStatusEnum;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'slug',
                'name',
                'description',
                'applies_to',
                'entity_type_id',
                'is_system',
                'is_active',
                'position',
                'published_version_number',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_templates_of_the_account_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Wine bottle', 'position' => 2]);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/templates');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Contact');
        $response->assertJsonPath('data.1.attributes.name', 'Wine bottle');
    }

    #[Test]
    public function it_lists_the_templates_nobody_may_apply_alongside_the_rest(): void
    {
        $user = User::factory()->create();
        Template::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Retired', 'position' => 1]);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/templates');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.is_active', false);
    }

    #[Test]
    public function it_marks_a_template_the_product_supplied_as_a_system_template(): void
    {
        $user = User::factory()->create();
        Template::factory()->supplied()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/templates')->assertJsonPath('data.0.attributes.is_system', true);
    }

    #[Test]
    public function it_does_not_list_templates_from_another_account(): void
    {
        $user = User::factory()->create();
        Template::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/templates');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_a_template(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity($entityType)->create([
            'account_id' => $user->account_id,
            'name' => 'Animal',
            'description' => 'Every creature the family keeps.',
        ]);
        $published = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 3,
        ]);
        $template->update(['published_version_id' => $published->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/templates/{$template->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $template->uuid);
        $response->assertJsonPath('data.attributes.name', 'Animal');
        $response->assertJsonPath('data.attributes.applies_to', 'entity');
        $response->assertJsonPath('data.attributes.entity_type_id', $entityType->uuid);
        $response->assertJsonPath('data.attributes.published_version_number', 3);
        $response->assertJsonPath('data.attributes.created_at', $template->created_at->timestamp);
    }

    #[Test]
    public function it_says_a_template_has_no_published_version_when_it_has_none(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/templates/{$template->uuid}")
            ->assertJsonPath('data.attributes.published_version_number', null);
    }

    #[Test]
    public function it_returns_not_found_for_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/templates/{$template->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_template_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/templates/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64')->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_caller_holding_no_token(): void
    {
        $this->json('GET', '/api/v1/templates')->assertUnauthorized();
    }

    #[Test]
    public function it_creates_a_template(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/templates', [
            'name' => 'Wine bottle',
            'applies_to' => 'contact',
            'description' => 'Everything the cellar holds.',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Wine bottle');
        $response->assertJsonPath('data.attributes.slug', 'wine-bottle');
        $response->assertJsonPath('data.attributes.is_active', true);
        $response->assertJsonPath('data.attributes.published_version_number', null);
        $this->assertDatabaseHas('templates', [
            'account_id' => $user->account_id,
            'name' => 'Wine bottle',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_opens_an_empty_draft_when_a_template_is_created(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/templates', ['name' => 'Wine bottle', 'applies_to' => 'contact'])->assertCreated();

        $template = Template::query()->where('account_id', $user->account_id)->sole();

        $this->assertDatabaseHas('template_versions', [
            'template_id' => $template->id,
            'version_number' => 1,
            'status' => VersionStatusEnum::Draft->value,
        ]);
    }

    #[Test]
    public function it_creates_a_template_describing_an_entity_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/templates', [
            'name' => 'Animal',
            'applies_to' => 'entity',
            'entity_type_id' => $entityType->uuid,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.applies_to', 'entity');
        $response->assertJsonPath('data.attributes.entity_type_id', $entityType->uuid);
    }

    #[Test]
    public function it_validates_the_name_and_what_a_template_applies_to_when_creating_one(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/templates', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'applies_to']);

        $this->json('POST', '/api/v1/templates', ['name' => 'Animal', 'applies_to' => 'planet'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['applies_to']);
    }

    #[Test]
    public function it_requires_an_entity_type_when_the_template_describes_an_entity(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/templates', ['name' => 'Animal', 'applies_to' => 'entity'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_type_id']);
    }

    #[Test]
    public function it_refuses_an_entity_type_from_another_account_when_creating_a_template(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/templates', [
            'name' => 'Animal',
            'applies_to' => 'entity',
            'entity_type_id' => $entityType->uuid,
        ])->assertUnprocessable()->assertJsonValidationErrors(['entity_type_id']);
    }

    #[Test]
    public function it_updates_a_template(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Contact',
            'slug' => 'contact',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/templates/{$template->uuid}", [
            'name' => 'Person',
            'description' => 'Anybody the family knows.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Person');
        $response->assertJsonPath('data.attributes.description', 'Anybody the family knows.');
        $response->assertJsonPath('data.attributes.slug', 'contact');
    }

    #[Test]
    public function it_changes_a_template_the_product_supplied_the_way_the_browser_does(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->supplied()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}", ['name' => 'Person', 'description' => null])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Person');
    }

    #[Test]
    public function it_returns_not_found_when_updating_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}", ['name' => 'Person'])->assertNotFound();
    }

    #[Test]
    public function it_deletes_a_template(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}")->assertNoContent();

        $this->assertSoftDeleted($template);
    }

    #[Test]
    public function it_refuses_to_delete_a_template_that_still_renders_records(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->used(4)->create(['template_id' => $template->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['template']);

        $this->assertNotSoftDeleted($template);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($template);
    }
}
