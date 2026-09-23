<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'description',
                'colour',
                'position',
                'is_default',
                'contact_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_vaults_of_the_account_in_the_order_the_account_arranged(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin', 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.1.attributes.name', 'Schrute Farms');
    }

    #[Test]
    public function it_does_not_list_the_vaults_of_another_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_lists_nothing_when_the_account_holds_no_vault(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_brings_an_absurd_page_size_back_into_range(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/vaults?per_page=999')->assertJsonPath('meta.per_page', 100);
        $this->json('GET', '/api/v1/vaults?per_page=0')->assertJsonPath('meta.per_page', 1);
        $this->json('GET', '/api/v1/vaults?per_page=-5')->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin', 'position' => 1]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults?per_page=1&page=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Schrute Farms');
    }

    #[Test]
    public function it_does_not_list_a_deleted_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $vault->delete();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/vaults');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_one_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
            'position' => 3,
            'contact_count' => 12,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'vault');
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.attributes.description', 'Everybody at the Scranton branch.');
        $response->assertJsonPath('data.attributes.colour', '#4f8a49');
        $response->assertJsonPath('data.attributes.position', 3);
        $response->assertJsonPath('data.attributes.is_default', false);
        $response->assertJsonPath('data.attributes.contact_count', 12);
        $response->assertJsonPath('data.attributes.created_at', $vault->created_at->timestamp);
        $response->assertJsonPath('data.links.self', route('api.v1.vaults.show', $vault));
    }

    #[Test]
    public function it_returns_not_found_for_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stranger->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_vault_that_does_not_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/vaults/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_deleted_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $vault->delete();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_numeric_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->id}")->assertNotFound();
    }

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'vault');
        $response->assertJsonPath('data.attributes.name', 'Dunder Mifflin');
        $response->assertJsonPath('data.attributes.description', 'Everybody at the Scranton branch.');
        $response->assertJsonPath('data.attributes.colour', '#4f8a49');
        $response->assertJsonPath('data.attributes.contact_count', 0);

        $vault = Vault::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
        ]);
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.links.self', route('api.v1.vaults.show', $vault));
    }

    #[Test]
    public function it_creates_a_vault_without_a_description_or_a_colour(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Schrute Farms',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.description', null);
        $response->assertJsonPath('data.attributes.colour', Vault::DEFAULT_COLOUR);
    }

    #[Test]
    public function it_puts_a_new_vault_at_the_end_of_the_list(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['name' => 'Schrute Farms']);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.position', 5);
    }

    #[Test]
    public function it_makes_the_first_vault_of_an_account_the_default_one(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['name' => 'Dunder Mifflin']);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', true);

        $second = $this->json('POST', '/api/v1/vaults', ['name' => 'Schrute Farms']);

        $second->assertCreated();
        $second->assertJsonPath('data.attributes.is_default', false);
    }

    #[Test]
    public function it_refuses_a_vault_without_a_name(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['description' => 'Nothing to call it.']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('vaults', 0);
    }

    #[Test]
    public function it_refuses_a_colour_the_product_does_not_offer(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'colour' => 'beige',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('colour');
        $this->assertDatabaseCount('vaults', 0);
    }

    #[Test]
    public function it_refuses_a_name_longer_than_the_limit(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['name' => str_repeat('a', 256)]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('vaults', 0);
    }

    #[Test]
    public function it_writes_the_creation_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/vaults', ['name' => 'Dunder Mifflin'])->assertCreated();

        $vault = Vault::query()->where('account_id', $user->account_id)->firstOrFail();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    #[Test]
    public function it_changes_the_wording_of_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch.',
            'colour' => '#4f8a49',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => 'The branch, and everybody who walks through it.',
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.id', $vault->uuid);
        $response->assertJsonPath('data.attributes.name', 'Scranton');
        $response->assertJsonPath('data.attributes.description', 'The branch, and everybody who walks through it.');
        $response->assertJsonPath('data.attributes.colour', '#9a3f2b');

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'name' => 'Scranton',
            'description' => 'The branch, and everybody who walks through it.',
            'colour' => '#9a3f2b',
        ]);
    }

    #[Test]
    public function it_clears_the_description_when_it_is_sent_as_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'description' => 'Everybody at the Scranton branch.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.description', null);
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'description' => null]);
    }

    #[Test]
    public function it_leaves_what_the_vault_holds_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 4,
            'contact_count' => 12,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.position', 4);
        $response->assertJsonPath('data.attributes.is_default', true);
        $response->assertJsonPath('data.attributes.contact_count', 12);
    }

    #[Test]
    public function it_refuses_a_change_that_omits_the_colour(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'colour' => '#4f8a49',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('colour');
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'colour' => '#4f8a49']);
    }

    #[Test]
    public function it_refuses_a_change_that_omits_the_description(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'description' => 'Everybody at the Scranton branch.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'colour' => '#9a3f2b',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('description');
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'description' => 'Everybody at the Scranton branch.',
        ]);
    }

    #[Test]
    public function it_refuses_a_change_that_omits_the_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'name' => 'Dunder Mifflin']);
    }

    #[Test]
    public function it_returns_not_found_when_changing_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$stranger->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('vaults', ['id' => $stranger->id, 'name' => 'Vance Refrigeration']);
    }

    #[Test]
    public function it_writes_the_change_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
        ])->assertOk();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultUpdate
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Scranton']
                && $job->changes['name'] === ['before' => 'Dunder Mifflin', 'after' => 'Scranton'],
        );
    }

    #[Test]
    public function it_deletes_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}");

        $response->assertNoContent();
        $this->assertSame('', $response->content());
        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_takes_the_contacts_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();

        $this->assertSoftDeleted('contacts', ['id' => $dwight->id, 'deleted_with_vault' => true]);
    }

    #[Test]
    public function it_revokes_the_private_links_into_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $ontoTheVault = ShareLink::factory()->forVault($vault)->create();
        $elsewhere = ShareLink::factory()->create();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();

        $this->assertNotNull($ontoTheVault->refresh()->revoked_at);
        $this->assertNull($elsewhere->refresh()->revoked_at);
    }

    #[Test]
    public function it_hands_the_default_title_to_the_first_vault_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id, 'position' => 1]);
        $next = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();

        $this->assertTrue($next->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_deletes_the_last_vault_of_an_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();

        $this->assertSame(0, Vault::query()->where('account_id', $user->account_id)->count());
        $this->assertSame(0, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_returns_not_found_when_deleting_a_vault_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$stranger->uuid}")->assertNotFound();

        $this->assertNotSoftDeleted('vaults', ['id' => $stranger->id]);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_the_same_vault_twice(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();
        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}")->assertNoContent();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDeletion
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    #[Test]
    public function it_makes_a_new_vault_the_default_one_when_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', true);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_takes_the_default_title_off_the_vault_that_held_it(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Schrute Farms',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', true);
        $this->assertFalse($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_leaves_the_default_alone_when_the_request_says_false(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Schrute Farms',
            'is_default' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.is_default', false);
        $this->assertTrue($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_leaves_the_default_alone_when_the_request_says_nothing(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/vaults', ['name' => 'Schrute Farms'])->assertCreated();

        $this->assertTrue($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_makes_a_vault_the_default_one_on_a_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
            'is_default' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_default', true);
        $this->assertFalse($held->refresh()->is_default);
        $this->assertSame(1, $this->defaultVaultCount($user));
    }

    #[Test]
    public function it_says_what_a_new_contact_in_the_vault_starts_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => Vault::query()->where('name', 'Dunder Mifflin')->sole()->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_says_what_a_new_contact_starts_from_on_a_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Scranton',
            'description' => null,
            'colour' => '#9a3f2b',
            'structure_version_id' => $chosen->structure->published_version_id,
        ])->assertOk();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_refuses_a_structure_version_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'structure_version_id' => $draft->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('structure_version_id');
        $this->assertDatabaseCount('vaults', 0);
    }

    #[Test]
    public function it_writes_each_decision_to_the_journal_as_its_own_entry(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/vaults', [
            'name' => 'Dunder Mifflin',
            'is_default' => true,
            'structure_version_id' => $chosen->structure->published_version_id,
        ])->assertCreated();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultCreation,
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDefaultSet,
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDefaultTemplateSet,
        );
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }

    private function defaultVaultCount(User $user): int
    {
        return Vault::query()
            ->where('account_id', $user->account_id)
            ->where('is_default', true)
            ->count();
    }

    #[Test]
    public function it_refuses_a_vault_past_the_ceiling_with_the_cap_named(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/vaults', ['name' => 'Sabre']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $response->assertJsonPath('errors.name.0', 'The free offer holds 1 vault, and this account already has it. Full access lifts the ceiling.');
        $this->assertSame(1, $user->account->vaults()->count());
    }

    #[Test]
    public function it_still_answers_and_edits_what_an_account_over_the_ceiling_holds(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/vaults')->assertOk()->assertJsonCount(2, 'data');
        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
            'name' => 'Utica',
            'description' => 'The branch that stayed open.',
            'colour' => '#9a3f2b',
        ])->assertOk();

        $this->assertSame('Utica', $vault->refresh()->name);
    }

    #[Test]
    public function it_reaches_a_vault_by_either_spelling_of_its_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}")->assertOk()->assertJsonPath('data.id', $vault->uuid);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")->assertOk()->assertJsonPath('data.id', $vault->uuid);
    }

    #[Test]
    public function it_reports_the_readable_address_beside_the_identifier(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $vault->uuid)
            ->assertJsonPath('data.attributes.slug', 'dunder-mifflin')
            ->assertJsonPath('data.links.self', route('api.v1.vaults.show', $vault));
    }

    #[Test]
    public function it_does_not_reach_the_readable_address_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = Vault::factory()->create(['name' => 'Schrute Farms']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$theirs->slug}")->assertNotFound();
        $this->json('GET', "/api/v1/vaults/{$theirs->uuid}")->assertNotFound();
    }
}
