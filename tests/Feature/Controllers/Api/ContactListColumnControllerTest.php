<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ContactListColumnSourceEnum;
use App\Models\ContactListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactListColumnControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [['source', 'section', 'field_key', 'heading', 'default_heading', 'is_visible', 'is_supplied']],
        'meta' => ['is_default'],
    ];

    #[Test]
    public function it_shows_the_default_columns_of_a_vault_that_chose_none(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-list-columns")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.source', 'email')
            ->assertJsonPath('data.3.is_visible', false)
            ->assertJsonPath('meta.is_default', true);
    }

    #[Test]
    public function it_replaces_the_columns_of_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $work = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']])['Work'];

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contact-list-columns", [
            'columns' => [
                ['source' => 'field', 'section' => $work->uuid, 'field_key' => 'company', 'heading' => 'Employer', 'is_visible' => true],
            ],
        ])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.0.section', $work->uuid)
            ->assertJsonPath('data.0.heading', 'Employer')
            ->assertJsonPath('data.0.default_heading', 'Company')
            ->assertJsonPath('meta.is_default', false);
    }

    #[Test]
    public function it_refuses_the_same_column_twice(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Nickname]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contact-list-columns", [
            'columns' => [
                ['source' => 'email', 'is_visible' => true],
                ['source' => 'email', 'is_visible' => true],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['columns']);

        $this->assertDatabaseHas('contact_list_columns', ['vault_id' => $vault->id, 'source' => 'nickname']);
    }

    #[Test]
    public function it_does_not_reach_a_vault_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/vaults/'.Vault::factory()->create()->uuid.'/contact-list-columns')
            ->assertNotFound();
    }
}
