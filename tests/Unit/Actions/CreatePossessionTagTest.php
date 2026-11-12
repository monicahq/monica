<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionTag;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_tag_with_the_first_vault_colour_when_none_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $possessionTag = new CreatePossessionTag(
            user: $user,
            name: '  <b>Insured</b> ',
        )->execute();

        $this->assertInstanceOf(PossessionTag::class, $possessionTag);
        $this->assertDatabaseHas('possession_tags', [
            'id' => $possessionTag->id,
            'account_id' => $user->account_id,
            'name' => 'Insured',
            'colour' => VaultColourEnum::Blue->value,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTagCreation
                && $job->parameters === ['name' => 'Insured'],
        );
    }

    #[Test]
    public function it_keeps_the_colour_it_is_given(): void
    {
        Queue::fake();

        $possessionTag = new CreatePossessionTag(
            user: User::factory()->create(),
            name: 'To sell',
            colour: VaultColourEnum::Green,
        )->execute();

        $this->assertSame(VaultColourEnum::Green->value, $possessionTag->colour);
    }

    #[Test]
    public function it_refuses_a_name_left_empty_once_markup_is_stripped(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePossessionTag(
            user: User::factory()->create(),
            name: ' <i></i> ',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_name_the_account_already_uses_ignoring_case(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

        try {
            new CreatePossessionTag(user: $user, name: 'insured')->execute();
            $this->fail('A name taken ignoring case was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    #[Test]
    public function it_accepts_a_name_another_account_uses(): void
    {
        Queue::fake();

        PossessionTag::factory()->create(['name' => 'Insured']);

        $possessionTag = new CreatePossessionTag(
            user: User::factory()->create(),
            name: 'Insured',
        )->execute();

        $this->assertModelExists($possessionTag);
    }
}
