<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetLifeEventTypeFields;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLifeEventTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_an_empty_list_for_a_type_with_no_fields(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $this->actingAs($user);

        $response = app(GetLifeEventTypeFields::class)->handle(new Request(['life_event_type' => $type->uuid]));

        $this->assertSame([], json_decode((string) $response->content(), true));
    }

    #[Test]
    public function it_reports_a_type_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create();

        MonicaServer::actingAs($user)->tool(GetLifeEventTypeFields::class, ['life_event_type' => $type->uuid])
            ->assertHasErrors();
    }
}
