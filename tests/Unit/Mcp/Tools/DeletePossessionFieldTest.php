<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionField;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionField::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_reports_a_field_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionField::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
        ])->assertHasErrors();

        $this->assertModelExists($field);
    }
}
