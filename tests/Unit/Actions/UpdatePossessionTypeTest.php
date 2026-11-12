<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionType;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_and_recolours_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Untitled type']);

        $updated = new UpdatePossessionType(
            user: $user,
            possessionType: $possessionType,
            name: 'Office equipment',
            colour: VaultColourEnum::Rust,
            icon: 'briefcase',
        )->execute();

        $this->assertInstanceOf(PossessionType::class, $updated);
        $this->assertDatabaseHas('possession_types', [
            'id' => $possessionType->id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Rust->value,
            'icon' => 'briefcase',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTypeUpdate
                && $job->parameters === ['name' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_removes_the_icon_when_given_an_empty_one(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'icon' => 'car']);

        new UpdatePossessionType(
            user: $user,
            possessionType: $possessionType,
            name: 'Company cars',
            colour: VaultColourEnum::Blue,
            icon: '',
        )->execute();

        $this->assertNull($possessionType->refresh()->icon);
    }

    #[Test]
    public function it_refuses_an_icon_outside_the_catalogue(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionType(
            user: $user,
            possessionType: $possessionType,
            name: 'Company cars',
            colour: VaultColourEnum::Blue,
            icon: 'beet',
        )->execute();
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionType(
            user: $user,
            possessionType: $possessionType,
            name: '   ',
            colour: VaultColourEnum::Blue,
            icon: null,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionType(
            user: $user,
            possessionType: $possessionType,
            name: 'Office equipment',
            colour: VaultColourEnum::Blue,
            icon: null,
        )->execute();
    }
}
