<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePreferences;
use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Enums\UserActionEnum;
use App\Helpers\LocaleCollation;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
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

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPreferencesUpdate
                && $job->account->id === $account->id
                && $job->user->id === $user->id,
        );
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
    public function it_rebuilds_the_ordering_keys_for_the_new_language(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'locale' => 'fr_FR']);
        $contact = Contact::factory()->create([
            'account_id' => $account->id,
            'first_name' => null,
            'middle_name' => null,
            'last_name' => 'Ñuñez',
        ]);

        $this->assertSame('nunez', $contact->fresh()->search_key);

        new UpdatePreferences(
            user: $user,
            locale: 'es_ES',
            timeFormat: TimeFormatEnum::TwentyFourHour,
        )->execute();

        $fresh = $contact->fresh();

        $this->assertSame('ñuñez', $fresh->search_key);
        $this->assertSame(LocaleCollation::sortKey($fresh->sort_name, 'es_ES'), $fresh->sort_key);
    }

    #[Test]
    public function it_leaves_the_language_and_the_keys_alone_when_the_rebuild_cannot_finish(): void
    {
        Queue::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'locale' => 'fr_FR']);
        $contact = Contact::factory()->create([
            'account_id' => $account->id,
            'first_name' => null,
            'middle_name' => null,
            'last_name' => 'Ñuñez',
        ]);

        DB::listen(function ($query) {
            if (preg_match('/^update\s+"?contacts"?/i', $query->sql) === 1) {
                throw new RuntimeException('the rebuild could not finish');
            }
        });

        try {
            new UpdatePreferences(
                user: $user,
                locale: 'es_ES',
                timeFormat: TimeFormatEnum::TwentyFourHour,
            )->execute();
            $this->fail('The rebuild was expected to fail.');
        } catch (RuntimeException) {
        }

        $this->assertSame('fr_FR', $user->fresh()->locale);
        $this->assertSame('nunez', $contact->fresh()->search_key);
    }

    #[Test]
    public function it_turns_readable_addresses_off_and_on(): void
    {
        Queue::fake();

        $user = User::factory()->create(['uses_readable_addresses' => true]);

        new UpdatePreferences(
            user: $user,
            locale: 'en',
            timeFormat: TimeFormatEnum::TwentyFourHour,
            usesReadableAddresses: false,
        )->execute();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'uses_readable_addresses' => false]);

        new UpdatePreferences(
            user: $user,
            locale: 'en',
            timeFormat: TimeFormatEnum::TwentyFourHour,
            usesReadableAddresses: true,
        )->execute();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'uses_readable_addresses' => true]);
    }

    #[Test]
    public function it_sends_every_record_of_the_account_to_search_again_in_another_language(): void
    {
        Queue::fake();
        $engine = $this->useRecordingSearchEngine();

        $user = User::factory()->create(['locale' => 'en']);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id]);
        $engine->forget();

        new UpdatePreferences(user: $user, locale: 'fr_FR', timeFormat: TimeFormatEnum::TwentyFourHour)->execute();

        $this->assertTrue($engine->wasSent($dwight));
    }
}
