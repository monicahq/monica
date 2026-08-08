<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePreferences;
use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePreferencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_preferences_of_a_user(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'locale' => 'en',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $result = new UpdatePreferences(
            user: $user,
            locale: 'fr_FR',
            timeFormat: TimeFormatEnum::TwelveHour,
            dateFormat: DateFormatEnum::MonthFirst,
            numberFormat: NumberFormatEnum::SpaceAndComma,
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'fr_FR',
            'time_format' => '12',
            'date_format' => 'm/d/Y',
            'number_format' => 'space_comma',
        ]);
    }

    #[Test]
    public function it_hands_the_date_and_the_numbers_back_to_the_language_when_no_format_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'date_format' => DateFormatEnum::YearFirst,
            'number_format' => NumberFormatEnum::DotAndComma,
        ]);

        new UpdatePreferences(
            user: $user,
            locale: 'en',
            timeFormat: TimeFormatEnum::TwentyFourHour,
        )->execute();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'date_format' => null,
            'number_format' => null,
        ]);
    }

    #[Test]
    public function it_logs_the_change(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new UpdatePreferences(
            user: $user,
            locale: 'de_DE',
            timeFormat: TimeFormatEnum::TwelveHour,
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPreferencesUpdate
                && $job->account->id === $account->id
                && $job->user->id === $user->id,
        );
    }
}
