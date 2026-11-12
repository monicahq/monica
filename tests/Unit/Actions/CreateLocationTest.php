<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLocation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_location_at_the_top_level_with_a_name_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $location = new CreateLocation(
            user: $user,
            name: '  Dunder Mifflin Scranton  ',
            emoji: '',
            address: '   ',
        )->execute();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'account_id' => $user->account_id,
            'parent_id' => null,
            'name' => 'Dunder Mifflin Scranton',
            'emoji' => null,
            'address' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationCreation
                && $job->parameters === ['name' => 'Dunder Mifflin Scranton'],
        );
    }

    #[Test]
    public function it_adds_a_location_inside_another_with_an_emoji_and_an_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton']);

        $location = new CreateLocation(
            user: $user,
            name: 'Warehouse',
            parent: $office,
            emoji: '📦',
            address: "1725 Slough Avenue\nScranton, PA",
        )->execute();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'parent_id' => $office->id,
            'emoji' => '📦',
            'address' => "1725 Slough Avenue\nScranton, PA",
        ]);
    }

    #[Test]
    public function it_refuses_a_name_that_holds_nothing_but_markup(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        try {
            new CreateLocation(user: $user, name: '<b></b>')->execute();
            $this->fail('A location was added without a name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertDatabaseCount('locations', 0);
        }
    }

    #[Test]
    public function it_refuses_a_parent_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stamford = Location::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateLocation(user: $user, name: 'Annex', parent: $stamford)->execute();
    }
}
