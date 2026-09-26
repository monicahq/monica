<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetLifeEventTypeFields;
use App\Mcp\Tools\ReplaceLifeEventTypeFields;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplaceLifeEventTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_fields_of_a_type(): void
    {
        $user = User::factory()->create();
        $type = $this->type($user);

        EricaServer::actingAs($user)->tool(ReplaceLifeEventTypeFields::class, [
            'life_event_type' => $type->uuid,
            'groups' => [
                [
                    'name' => 'Ceremony',
                    'fields' => [
                        ['label' => 'Venue', 'type' => 'text', 'is_required' => true],
                        ['label' => 'Date', 'type' => 'date'],
                    ],
                ],
            ],
            'fields' => [
                ['label' => 'Speech', 'type' => 'textarea'],
            ],
        ])->assertOk()->assertHasNoErrors()->assertSee(['Ceremony', 'Venue', 'Date', 'Speech']);
    }

    #[Test]
    public function it_keeps_a_field_sent_back_under_its_key(): void
    {
        $user = User::factory()->create();
        $type = $this->type($user);

        EricaServer::actingAs($user)->tool(ReplaceLifeEventTypeFields::class, [
            'life_event_type' => $type->uuid,
            'fields' => [
                ['label' => 'Venue', 'type' => 'text'],
                ['label' => 'Date', 'type' => 'date'],
            ],
        ])->assertOk()->assertHasNoErrors();

        $key = $this->fields($user, $type)[0]['attributes']['fields'][0]['attributes']['key'];

        EricaServer::actingAs($user)->tool(ReplaceLifeEventTypeFields::class, [
            'life_event_type' => $type->uuid,
            'fields' => [
                ['key' => $key, 'label' => 'Place', 'type' => 'text'],
            ],
        ])->assertOk()->assertHasNoErrors();

        $fields = $this->fields($user, $type)[0]['attributes']['fields'];

        $this->assertCount(1, $fields);
        $this->assertSame($key, $fields[0]['attributes']['key']);
        $this->assertSame('Place', $fields[0]['attributes']['label']);
    }

    #[Test]
    public function it_refuses_a_kind_of_field_the_product_does_not_offer(): void
    {
        $user = User::factory()->create();
        $type = $this->type($user);

        EricaServer::actingAs($user)->tool(ReplaceLifeEventTypeFields::class, [
            'life_event_type' => $type->uuid,
            'fields' => [
                ['label' => 'Dundie', 'type' => 'dundie'],
            ],
        ])->assertHasErrors(['The selected fields.0.type is invalid.']);

        $this->assertSame([], $this->fields($user, $type));
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEventTypes.fields.update', app(ReplaceLifeEventTypeFields::class)->mirrors());
    }

    private function type(User $user): LifeEventType
    {
        return LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function fields(User $user, LifeEventType $type): array
    {
        $this->actingAs($user);

        $response = app(GetLifeEventTypeFields::class)->handle(new Request(['life_event_type' => $type->uuid]));

        return json_decode((string) $response->content(), true);
    }
}
