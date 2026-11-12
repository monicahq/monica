<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportPossessionType;
use App\Actions\ImportPossessionType;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFile;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_new_type_with_the_same_structure_as_the_exported_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $original = PossessionType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'briefcase',
        ]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $original->id, 'name' => 'Purchase', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Price', 'kind' => PossessionFieldKindEnum::Number, 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop', 'position' => 2]);
        PossessionField::factory()->create(['possession_type_id' => $original->id, 'name' => 'Serial number', 'position' => 1]);
        PossessionField::factory()->create([
            'possession_type_id' => $original->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
            'position' => 2,
        ]);

        $exported = new ExportPossessionType(user: $user, possessionType: $original)->execute();

        $imported = new ImportPossessionType(
            user: $user,
            raw: PossessionTypeFile::encode($exported),
        )->execute();

        $this->assertInstanceOf(PossessionType::class, $imported);
        $this->assertNotSame($original->id, $imported->id);
        $this->assertNotSame($original->uuid, $imported->uuid);
        $this->assertSame($user->account_id, $imported->account_id);
        $this->assertDatabaseCount('possession_types', 2);
        $this->assertSame(
            $exported,
            new ExportPossessionType(user: $user, possessionType: $imported)->execute(),
        );
    }

    #[Test]
    public function it_creates_nothing_and_throws_for_a_file_with_problems(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        try {
            new ImportPossessionType(
                user: $user,
                raw: json_encode([
                    'format' => 'possession_type',
                    'format_version' => 1,
                    'possession_type' => [
                        'name' => 'Office equipment',
                        'fields' => [['name' => 'Condition', 'kind' => 'wattage']],
                    ],
                ]),
            )->execute();
            $this->fail('The file was imported.');
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $this->assertNotEmpty($exception->problems);
        }

        $this->assertDatabaseCount('possession_types', 0);
        $this->assertDatabaseCount('possession_field_groups', 0);
        $this->assertDatabaseCount('possession_fields', 0);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_logs_the_import(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new ImportPossessionType(
            user: $user,
            raw: json_encode([
                'format' => 'possession_type',
                'format_version' => 1,
                'possession_type' => ['name' => 'Vehicle'],
            ]),
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTypeImport
                && $job->parameters === ['name' => 'Vehicle'],
        );
    }
}
