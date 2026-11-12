<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionField;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_untitled_text_field_last_among_the_standalone_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);

        $field = new CreatePossessionField(
            user: $user,
            possessionType: $possessionType,
        )->execute();

        $this->assertInstanceOf(PossessionField::class, $field);
        $this->assertDatabaseHas('possession_fields', [
            'id' => $field->id,
            'account_id' => $user->account_id,
            'possession_type_id' => $possessionType->id,
            'possession_field_group_id' => null,
            'kind' => PossessionFieldKindEnum::Text->value,
            'position' => 3,
        ]);
        $this->assertNotSame('', $field->name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldCreation
                && $job->parameters === ['name' => $field->name, 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_places_a_field_last_within_its_group_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 3]);
        PossessionField::factory()->inGroup($purchase)->create(['position' => 1]);

        $field = new CreatePossessionField(
            user: $user,
            possessionType: $possessionType,
            group: $purchase,
            name: 'Price',
            kind: PossessionFieldKindEnum::Number,
        )->execute();

        $this->assertDatabaseHas('possession_fields', [
            'id' => $field->id,
            'possession_field_group_id' => $purchase->id,
            'name' => 'Price',
            'kind' => PossessionFieldKindEnum::Number->value,
            'position' => 2,
        ]);
    }

    #[Test]
    public function it_keeps_the_choices_of_a_select_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $field = new CreatePossessionField(
            user: $user,
            possessionType: $possessionType,
            name: 'Condition',
            kind: PossessionFieldKindEnum::Select,
            options: [' New ', 'Used', ''],
        )->execute();

        $this->assertSame(['New', 'Used'], $field->options);
    }

    #[Test]
    public function it_refuses_a_group_of_another_type(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionField(
            user: $user,
            possessionType: $possessionType,
            group: $group,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionField(
            user: $user,
            possessionType: $possessionType,
        )->execute();
    }
}
