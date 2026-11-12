<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportPossessionType;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_the_groups_and_fields_in_order_with_their_kinds_and_choices(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'briefcase',
        ]);
        $warranty = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Warranty', 'position' => 2]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop', 'position' => 2]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Price', 'kind' => PossessionFieldKindEnum::Number, 'position' => 1]);
        PossessionField::factory()->inGroup($warranty)->create(['name' => 'Warranty end', 'kind' => PossessionFieldKindEnum::Date, 'position' => 1]);
        PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
            'position' => 2,
        ]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number', 'position' => 1]);

        $file = new ExportPossessionType(
            user: $user,
            possessionType: $possessionType,
        )->execute();

        $this->assertSame([
            'format' => 'possession_type',
            'format_version' => 1,
            'possession_type' => [
                'name' => 'Office equipment',
                'colour' => VaultColourEnum::Teal->value,
                'icon' => 'briefcase',
                'groups' => [
                    [
                        'name' => 'Purchase',
                        'fields' => [
                            ['name' => 'Price', 'kind' => 'number'],
                            ['name' => 'Shop', 'kind' => 'text'],
                        ],
                    ],
                    [
                        'name' => 'Warranty',
                        'fields' => [
                            ['name' => 'Warranty end', 'kind' => 'date'],
                        ],
                    ],
                ],
                'fields' => [
                    ['name' => 'Serial number', 'kind' => 'text'],
                    ['name' => 'Condition', 'kind' => 'select', 'options' => ['New', 'Used']],
                ],
            ],
        ], $file);
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ExportPossessionType(
            user: $user,
            possessionType: $possessionType,
        )->execute();
    }
}
