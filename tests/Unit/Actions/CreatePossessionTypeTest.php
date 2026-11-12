<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionType;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_untitled_type_in_the_first_vault_colour(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $possessionType = new CreatePossessionType(
            user: $user,
        )->execute();

        $this->assertInstanceOf(PossessionType::class, $possessionType);
        $this->assertDatabaseHas('possession_types', [
            'id' => $possessionType->id,
            'account_id' => $user->account_id,
            'colour' => VaultColourEnum::Blue->value,
        ]);
        $this->assertNotSame('', $possessionType->name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTypeCreation
                && $job->parameters === ['name' => $possessionType->name],
        );
    }

    #[Test]
    public function it_adds_a_type_with_the_name_and_colour_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $possessionType = new CreatePossessionType(
            user: $user,
            name: '  Office equipment  ',
            colour: VaultColourEnum::Teal,
            icon: 'briefcase',
        )->execute();

        $this->assertDatabaseHas('possession_types', [
            'id' => $possessionType->id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'briefcase',
        ]);
    }

    #[Test]
    public function it_adds_a_type_without_an_icon_when_given_an_empty_one(): void
    {
        $user = User::factory()->create();

        $possessionType = new CreatePossessionType(user: $user, name: 'Dundies', icon: '')->execute();

        $this->assertNull($possessionType->icon);
    }

    #[Test]
    public function it_refuses_an_icon_outside_the_catalogue(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new CreatePossessionType(user: $user, name: 'Dundies', icon: 'beet')->execute();
    }

    #[Test]
    public function it_refuses_a_name_that_holds_nothing_but_markup(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new CreatePossessionType(
            user: $user,
            name: '<b></b>',
        )->execute();
    }
}
