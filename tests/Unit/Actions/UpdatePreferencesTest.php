<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePreferences;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
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

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'locale' => 'en',
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);

        $result = new UpdatePreferences(
            user: $user,
            locale: 'fr_FR',
            timeFormat: TimeFormatEnum::TwelveHour,
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'fr_FR',
            'time_format' => '12',
        ]);
    }

    #[Test]
    public function it_logs_the_change(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        new UpdatePreferences(
            user: $user,
            locale: 'de_DE',
            timeFormat: TimeFormatEnum::TwelveHour,
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPreferencesUpdate
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }
}
