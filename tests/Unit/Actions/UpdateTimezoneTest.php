<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTimezone;
use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTimezoneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_timezone_of_a_user(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $result = new UpdateTimezone(
            user: $user,
            timezone: 'Europe/Lisbon',
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Lisbon',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserTimezoneUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Europe/Lisbon'],
        );
    }

    #[Test]
    public function it_leaves_the_language_and_the_formats_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'timezone' => 'UTC',
            'locale' => 'fr_FR',
            'time_format' => TimeFormatEnum::TwelveHour,
            'date_format' => DateFormatEnum::YearFirst,
            'number_format' => NumberFormatEnum::SpaceAndComma,
        ]);

        new UpdateTimezone(
            user: $user,
            timezone: 'Asia/Tokyo',
        )->execute();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Asia/Tokyo',
            'locale' => 'fr_FR',
            'time_format' => '12',
            'date_format' => 'Y-m-d',
            'number_format' => 'space_comma',
        ]);
    }
}
