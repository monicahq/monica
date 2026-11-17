<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTags;

use App\Enums\VaultColourEnum;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTagControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'colour',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_possession_tags_of_the_account_sorted_by_name(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell']);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);
        PossessionTag::factory()->create(['name' => 'Dundie award']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/possession-tags');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Insured');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_shows_a_possession_tag(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/possession-tags/{$possessionTag->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.type', 'possession_tag')
            ->assertJsonPath('data.attributes.name', 'Fragile');
    }

    #[Test]
    public function it_answers_not_found_for_a_tag_of_another_account(): void
    {
        $possessionTag = PossessionTag::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/possession-tags/{$possessionTag->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_creates_a_possession_tag_in_blue_when_no_colour_is_given(): void
    {
        Queue::fake();

        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/possession-tags', ['name' => 'Heirloom'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.name', 'Heirloom')
            ->assertJsonPath('data.attributes.colour', VaultColourEnum::Blue->value);
    }

    #[Test]
    public function it_refuses_a_duplicate_name_and_a_colour_outside_the_palette(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/possession-tags', ['name' => 'INSURED'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->json('POST', '/api/v1/possession-tags', ['name' => 'Fragile', 'colour' => '#000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['colour']);
    }

    #[Test]
    public function it_updates_a_possession_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Paperwork filed']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-tags/{$possessionTag->uuid}", ['name' => 'Paperwork done', 'colour' => VaultColourEnum::Teal->value])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.name', 'Paperwork done')
            ->assertJsonPath('data.attributes.colour', VaultColourEnum::Teal->value);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Paperwork filed', 'colour' => VaultColourEnum::Rust->value]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-tags/{$possessionTag->uuid}", ['name' => 'Paperwork done'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Paperwork done')
            ->assertJsonPath('data.attributes.colour', VaultColourEnum::Rust->value);
    }

    #[Test]
    public function it_refuses_a_name_or_a_colour_sent_as_null(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Paperwork filed']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-tags/{$possessionTag->uuid}", ['name' => null, 'colour' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'colour']);
    }

    #[Test]
    public function it_deletes_a_possession_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/possession-tags/{$possessionTag->uuid}")->assertNoContent();

        $this->assertModelMissing($possessionTag);
    }
}
