<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\PossessionFieldKindEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionField;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionField::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
            'name' => 'State',
        ])->assertOk()->assertHasNoErrors()->assertSee('State');

        $field->refresh();
        $this->assertSame('State', $field->name);
        $this->assertSame(PossessionFieldKindEnum::Select, $field->kind);
        $this->assertSame(['New', 'Used'], $field->options);
    }

    #[Test]
    public function it_clears_the_options_when_they_are_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionField::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
            'options' => null,
        ])->assertOk()->assertHasNoErrors();

        $this->assertEmpty($field->refresh()->options);
    }

    #[Test]
    public function it_reports_a_field_of_another_type_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id, 'name' => 'Shop']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionField::class, [
            'possession_type' => $type->uuid,
            'field' => $field->uuid,
            'name' => 'Store',
        ])->assertHasErrors();

        $this->assertSame('Shop', $field->refresh()->name);
    }
}
