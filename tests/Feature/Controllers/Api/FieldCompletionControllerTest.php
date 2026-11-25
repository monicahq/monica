<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class FieldCompletionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'template',
                'version',
                'records',
                'sections' => [['name', 'fields' => [['key', 'label', 'filled', 'offered_to', 'percentage', 'available_from']]]],
                'hidden_count',
            ],
        ],
    ];

    #[Test]
    public function it_gives_the_completion_of_each_field(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $response = $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics/fields", [
            'template' => $fixture->pet->uuid,
            'order' => 'form',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.sections.0.fields.1.filled', 5);
        $response->assertJsonPath('data.attributes.sections.0.fields.1.offered_to', 11);
    }

    #[Test]
    public function it_counts_a_sensitive_field_without_naming_it_for_a_key_without_the_scope(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->fields['microchip']->updateQuietly(['is_sensitive' => true]);

        $this->withToken($fixture->user->createToken('Dwight’s script')->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics/fields", ['template' => $fixture->pet->uuid])
            ->assertJsonPath('data.attributes.hidden_count', 1);
    }

    #[Test]
    public function it_names_a_sensitive_field_for_a_key_holding_the_scope(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->fields['microchip']->updateQuietly(['is_sensitive' => true]);

        $this->withToken($fixture->user->createToken('Dwight’s script', [ApiKeyScopeEnum::SensitiveFields->value])->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics/fields", ['template' => $fixture->pet->uuid])
            ->assertJsonPath('data.attributes.hidden_count', 0);
    }

    #[Test]
    public function it_validates_the_order(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics/fields", [
            'template' => $fixture->pet->uuid,
            'order' => 'alphabetical',
        ])->assertUnprocessable()->assertJsonValidationErrors('order');
    }

    #[Test]
    public function it_returns_not_found_for_a_template_of_another_kind(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/contacts/statistics/fields", ['template' => $fixture->pet->uuid])
            ->assertNotFound();
    }
}
