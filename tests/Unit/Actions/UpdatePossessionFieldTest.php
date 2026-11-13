<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionField;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_field_and_changes_its_kind(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Untitled field']);

        $updated = new UpdatePossessionField(
            user: $user,
            field: $field,
            name: 'Warranty end',
            kind: PossessionFieldKindEnum::Date,
        )->execute();

        $this->assertInstanceOf(PossessionField::class, $updated);
        $this->assertDatabaseHas('possession_fields', [
            'id' => $field->id,
            'name' => 'Warranty end',
            'kind' => PossessionFieldKindEnum::Date->value,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldUpdate
                && $job->parameters === ['name' => 'Warranty end', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_keeps_trimmed_distinct_choices_and_drops_blank_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $updated = new UpdatePossessionField(
            user: $user,
            field: $field,
            name: 'Condition',
            kind: PossessionFieldKindEnum::Select,
            options: ['  New ', '', '   ', 'Used', 'Broken'],
        )->execute();

        $this->assertSame(['New', 'Used', 'Broken'], $updated->options);
        $this->assertSame(['New', 'Used', 'Broken'], $field->refresh()->options);
    }

    #[Test]
    public function it_clears_the_choices_when_the_kind_leaves_select(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
        ]);

        new UpdatePossessionField(
            user: $user,
            field: $field,
            name: 'Condition',
            kind: PossessionFieldKindEnum::Text,
            options: ['New', 'Used'],
        )->execute();

        $this->assertNull($field->refresh()->options);
    }

    #[Test]
    public function it_refuses_a_choice_repeated_in_another_case(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionField(
            user: $user,
            field: $field,
            name: 'Condition',
            kind: PossessionFieldKindEnum::Select,
            options: ['New', 'new'],
        )->execute();
    }

    #[Test]
    public function it_deletes_the_values_of_a_field_changing_kind_and_keeps_them_on_a_rename(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $plate = PossessionField::factory()->create(['possession_type_id' => $type->id, 'kind' => PossessionFieldKindEnum::Text]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $type->id, 'kind' => PossessionFieldKindEnum::Text]);
        $plateValue = PossessionFieldValue::factory()->create(['possession_field_id' => $plate->id]);
        $mileageValue = PossessionFieldValue::factory()->create(['possession_field_id' => $mileage->id, 'value' => '120000']);

        new UpdatePossessionField(user: $user, field: $plate, name: 'Licence plate', kind: PossessionFieldKindEnum::Text)->execute();
        new UpdatePossessionField(user: $user, field: $mileage, name: 'Mileage', kind: PossessionFieldKindEnum::Number)->execute();

        $this->assertModelExists($plateValue);
        $this->assertModelMissing($mileageValue);
    }

    #[Test]
    public function it_deletes_the_values_holding_a_removed_choice_and_keeps_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $colour = PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['Red', 'Blue'],
        ]);
        $transAm = PossessionFieldValue::factory()->create(['possession_field_id' => $colour->id, 'value' => 'Red']);
        $dundie = PossessionFieldValue::factory()->create(['possession_field_id' => $colour->id, 'value' => 'Blue']);

        new UpdatePossessionField(user: $user, field: $colour, name: 'Colour', kind: PossessionFieldKindEnum::Select, options: ['Crimson', 'Blue'])->execute();

        $this->assertModelMissing($transAm);
        $this->assertModelExists($dundie);
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionField(
            user: $user,
            field: $field,
            name: '   ',
            kind: PossessionFieldKindEnum::Text,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_field_of_another_account(): void
    {
        $user = User::factory()->create();
        $field = PossessionField::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionField(
            user: $user,
            field: $field,
            name: 'Warranty end',
            kind: PossessionFieldKindEnum::Date,
        )->execute();
    }
}
