<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangePreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'from',
                'to',
                'to_version',
                'counts' => ['ready', 'unchanged', 'set_aside', 'restored', 'reminders_paused', 'reminders_resumed'],
                'set_aside',
                'restored',
                'reminders_paused',
                'reminders_resumed',
                'blocked',
                'hidden_count',
            ],
        ],
    ];

    #[Test]
    public function it_previews_a_change_of_many_records(): void
    {
        $fixture = AnimalsVault::build();
        $response = $this->withToken($fixture->user->createToken('Dwight’s script', [ApiKeyScopeEnum::SensitiveFields->value])->plainTextToken)->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-change-preview", [
            'from' => $fixture->horse->uuid,
            'to' => $fixture->pet->uuid,
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.counts.ready', 4);
        $response->assertJsonPath('data.attributes.set_aside.0', ['label' => 'Stable name', 'section' => 'Stable', 'records' => 2]);
    }

    #[Test]
    public function it_refuses_a_target_that_is_turned_off(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->pet->update(['is_active' => false]);
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-change-preview", [
            'from' => $fixture->horse->uuid,
            'to' => $fixture->pet->uuid,
        ])->assertUnprocessable()->assertJsonPath('errors.to.0', 'This template is turned off, so no record can be put on it.');
    }

    #[Test]
    public function it_validates_the_destination(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-change-preview", [
            'from' => $fixture->horse->uuid,
        ])->assertUnprocessable()->assertJsonValidationErrors('to');
    }
}
