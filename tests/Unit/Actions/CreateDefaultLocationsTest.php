<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultLocations;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultLocationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_a_house_and_five_rooms_inside_it(): void
    {
        Queue::fake();
        $user = $this->michael();

        $locations = new CreateDefaultLocations(user: $user)->execute();

        $this->assertCount(6, $locations);

        $house = Location::query()->where('account_id', $user->account_id)->whereNull('parent_id')->sole();
        $this->assertEquals('🏠', $house->emoji);
        $this->assertNull($house->address);

        $this->assertEquals(
            ['🛋️', '🍳', '🛏️', '🚿', '📦'],
            Location::query()->where('parent_id', $house->id)->orderBy('id')->pluck('emoji')->all(),
        );

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationsSeeded);
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_writes_the_names_in_the_language_of_the_user(): void
    {
        $user = $this->michael(locale: 'fr_FR');
        $englishUser = $this->michael();

        new CreateDefaultLocations(user: $user)->execute();
        new CreateDefaultLocations(user: $englishUser)->execute();

        $this->assertNotEquals(
            $englishUser->account->locations()->orderBy('id')->pluck('name')->all(),
            $user->account->locations()->orderBy('id')->pluck('name')->all(),
        );
    }

    #[Test]
    public function it_leaves_an_account_that_already_has_a_location_alone(): void
    {
        Queue::fake();
        $user = $this->michael();
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton Business Park']);

        $locations = new CreateDefaultLocations(user: $user)->execute();

        $this->assertEquals([], $locations);
        $this->assertEquals(1, Location::query()->where('account_id', $user->account_id)->count());
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_adds_nothing_when_it_runs_a_second_time(): void
    {
        Queue::fake();
        $user = $this->michael();

        new CreateDefaultLocations(user: $user)->execute();
        new CreateDefaultLocations(user: $user)->execute();

        $this->assertEquals(6, Location::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_does_not_bring_back_a_room_somebody_deleted(): void
    {
        Queue::fake();
        $user = $this->michael();
        new CreateDefaultLocations(user: $user)->execute();
        Location::query()->where('account_id', $user->account_id)->where('name', 'Bathroom')->delete();

        new CreateDefaultLocations(user: $user)->execute();

        $this->assertEquals(5, Location::query()->where('account_id', $user->account_id)->count());
        $this->assertFalse(Location::query()->where('account_id', $user->account_id)->where('name', 'Bathroom')->exists());
    }

    private function michael(string $locale = 'en'): User
    {
        $account = Account::factory()->create();

        return $this->assignUserToAccount(user: User::factory()->make(['locale' => $locale]), account: $account);
    }
}
