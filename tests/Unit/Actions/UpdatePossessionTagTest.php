<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionTag;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_and_recolours_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Paperwork filed']);

        $updated = new UpdatePossessionTag(
            user: $user,
            possessionTag: $possessionTag,
            name: 'Paperwork done',
            colour: VaultColourEnum::Teal,
        )->execute();

        $this->assertInstanceOf(PossessionTag::class, $updated);
        $this->assertDatabaseHas('possession_tags', [
            'id' => $possessionTag->id,
            'name' => 'Paperwork done',
            'colour' => VaultColourEnum::Teal->value,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTagUpdate
                && $job->parameters === ['name' => 'Paperwork done'],
        );
    }

    #[Test]
    public function it_accepts_a_change_of_case_of_its_own_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

        new UpdatePossessionTag(
            user: $user,
            possessionTag: $possessionTag,
            name: 'INSURED',
            colour: VaultColourEnum::Blue,
        )->execute();

        $this->assertSame('INSURED', $possessionTag->refresh()->name);
    }

    #[Test]
    public function it_refuses_the_name_of_another_tag_ignoring_case(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Heirloom']);

        $this->expectException(ValidationException::class);

        new UpdatePossessionTag(
            user: $user,
            possessionTag: $possessionTag,
            name: 'fragile',
            colour: VaultColourEnum::Blue,
        )->execute();
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionTag(
            user: $user,
            possessionTag: $possessionTag,
            name: '   ',
            colour: VaultColourEnum::Blue,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_tag_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionTag(
            user: User::factory()->create(),
            possessionTag: PossessionTag::factory()->create(),
            name: 'Insured',
            colour: VaultColourEnum::Blue,
        )->execute();
    }
}
