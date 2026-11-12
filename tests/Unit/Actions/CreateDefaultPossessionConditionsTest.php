<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultPossessionConditions;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultPossessionConditionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_six_conditions_from_best_to_worst(): void
    {
        Queue::fake();
        $user = $this->michael();

        $possessionConditions = new CreateDefaultPossessionConditions(user: $user)->execute();

        $this->assertCount(6, $possessionConditions);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $user->account->possessionConditions()->pluck('position')->all());
        $this->assertDatabaseHas('possession_conditions', [
            'account_id' => $user->account_id,
            'position' => 1,
            'colour' => '#4f8a49',
            'icon' => null,
        ]);
        $this->assertDatabaseHas('possession_conditions', [
            'account_id' => $user->account_id,
            'position' => 6,
            'colour' => '#4a4a52',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionConditionsSeeded);
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_writes_the_names_in_the_language_of_the_user(): void
    {
        $user = $this->michael(locale: 'fr_FR');
        $englishUser = $this->michael();

        new CreateDefaultPossessionConditions(user: $user)->execute();
        new CreateDefaultPossessionConditions(user: $englishUser)->execute();

        $this->assertNotEquals(
            $englishUser->account->possessionConditions()->pluck('name')->all(),
            $user->account->possessionConditions()->pluck('name')->all(),
        );
    }

    #[Test]
    public function it_leaves_an_account_that_already_has_a_condition_alone(): void
    {
        Queue::fake();
        $user = $this->michael();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint']);

        $possessionConditions = new CreateDefaultPossessionConditions(user: $user)->execute();

        $this->assertEquals([], $possessionConditions);
        $this->assertEquals(1, PossessionCondition::query()->where('account_id', $user->account_id)->count());
        Queue::assertNothingPushed();
    }

    private function michael(string $locale = 'en'): User
    {
        $account = Account::factory()->create();

        return $this->assignUserToAccount(user: User::factory()->make(['locale' => $locale]), account: $account);
    }
}
