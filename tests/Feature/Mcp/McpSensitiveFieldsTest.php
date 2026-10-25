<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpSensitiveFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_a_sensitive_value_from_a_key_without_the_ability(): void
    {
        [$user, $vault, $lifeEvent] = $this->doctorVisit();

        $answer = $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->postJson('/mcp', $this->getLifeEvent($vault, $lifeEvent))
            ->assertOk();

        $this->assertSame(null, $this->fields($answer->json('result.content.0.text'))[0]['value']);
        $this->assertTrue($this->fields($answer->json('result.content.0.text'))[0]['is_hidden']);
    }

    #[Test]
    public function it_shows_a_sensitive_value_to_a_key_holding_the_ability(): void
    {
        [$user, $vault, $lifeEvent] = $this->doctorVisit();

        $answer = $this->withToken($user->createToken('Dwight’s trusted script', ['sensitive-fields'])->plainTextToken)
            ->postJson('/mcp', $this->getLifeEvent($vault, $lifeEvent))
            ->assertOk();

        $this->assertSame('Vasectomy', $this->fields($answer->json('result.content.0.text'))[0]['value']);
        $this->assertFalse($this->fields($answer->json('result.content.0.text'))[0]['is_hidden']);
    }

    /** @return array{User, Vault, LifeEvent} */
    private function doctorVisit(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-03-01', 'title' => 'Doctor visit', 'participant_count' => 1]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jim->id, 'position' => 0]);
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Text, 'label' => 'Diagnosis', 'is_sensitive' => true]);
        FieldValue::factory()->create([
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'field_id' => $field->id,
            'value_string' => 'Vasectomy',
        ]);

        return [$user, $vault, $lifeEvent];
    }

    /** @return array<string, mixed> */
    private function getLifeEvent(Vault $vault, LifeEvent $lifeEvent): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => ['name' => 'get-life-event', 'arguments' => ['vault' => $vault->uuid, 'life_event' => $lifeEvent->uuid]],
        ];
    }

    /** @return list<array{value: mixed, is_hidden: bool}> */
    private function fields(string $json): array
    {
        return json_decode($json, true)['attributes']['fields'];
    }
}
