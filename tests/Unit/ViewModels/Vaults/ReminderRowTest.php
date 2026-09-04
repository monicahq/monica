<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderRow;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderRowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_the_date_the_cadence_and_the_person(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Europe/Paris',
            'date_format' => DateFormatEnum::MonthSpelled,
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $rule = $this->ruleAbout($user, $contact);

        $row = $this->rowFor($user, $rule, '2027-05-12 07:00:00');

        $this->assertSame('12', $row['day']);
        $this->assertSame('2027', $row['year']);
        $this->assertSame('Every year', $row['cadence']);
        $this->assertSame('calendar-repeat', $row['cadenceIcon']);
        $this->assertSame($contact->name, $row['contactName']);
        $this->assertSame(route('contacts.show', ['vault' => $vault->id, 'contact' => $contact]), $row['contactUrl']);
        $this->assertNull($row['avatarUrl']);
        $this->assertFalse($row['isLate']);
    }

    #[Test]
    public function it_says_a_rule_that_fires_once_fires_once(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $rule->cadence = ReminderCadenceEnum::Once;
        $rule->save();

        $row = $this->rowFor($user, $rule, '2027-05-12 07:00:00');

        $this->assertSame('One time', $row['cadence']);
        $this->assertSame('calendar', $row['cadenceIcon']);
    }

    #[Test]
    public function it_counts_the_years_of_a_rhythm_somebody_set_themselves(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $rule->frequency_interval = 3;
        $rule->save();

        $this->assertSame('Every 3 years', $this->rowFor($user, $rule, '2027-05-12 07:00:00')['cadence']);
    }

    /**
     * The instant is due at nine in Paris, which is where the rule was made. A
     * reader in Toronto sees the Paris hour, because that is when the reminder
     * actually fires.
     */
    #[Test]
    public function it_names_the_instant_in_the_zone_frozen_on_the_rule(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/Toronto',
            'date_format' => DateFormatEnum::MonthSpelled,
            'time_format' => TimeFormatEnum::TwentyFourHour,
        ]);
        $rule = $this->ruleAbout($user);

        $row = $this->rowFor($user, $rule, '2027-05-12 07:00:00');

        $this->assertStringContainsString('09:00', $row['exact']);
        $this->assertStringContainsString('Europe/Paris', $row['exact']);
        $this->assertStringContainsString('UTC+02:00', $row['exact']);
        $this->assertStringContainsString('Will be sent', $row['exact']);
    }

    #[Test]
    public function it_words_a_deadline_nobody_dealt_with_as_already_gone(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);

        $row = $this->rowFor($user, $rule, CarbonImmutable::now()->subDays(4)->toDateTimeString());

        $this->assertTrue($row['isLate']);
        $this->assertStringContainsString('Sent', $row['exact']);
        $this->assertStringNotContainsString('Will be sent', $row['exact']);
        $this->assertStringContainsString('ago', $row['relative']);
    }

    #[Test]
    public function it_marks_a_birthday_and_falls_back_to_a_calendar_for_anything_somebody_invented(): void
    {
        $user = User::factory()->create();

        $birthday = Field::factory()->recurringDate()->create([
            'account_id' => $user->account_id,
            'key' => DefaultFieldEnum::Birthday->value,
        ]);
        $invented = Field::factory()->recurringDate()->create([
            'account_id' => $user->account_id,
            'key' => 'the-day-we-met',
        ]);

        $ofBirthday = $this->rowFor($user, $this->ruleAbout($user, field: $birthday), '2027-05-12 07:00:00');
        $ofInvented = $this->rowFor($user, $this->ruleAbout($user, field: $invented), '2027-05-12 07:00:00');

        $this->assertSame('gift', $ofBirthday['icon']);
        $this->assertSame('calendar', $ofInvented['icon']);
    }

    #[Test]
    public function it_still_draws_a_row_whose_rule_has_been_taken_away(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $occurrence = $this->occurrenceFor($rule, '2027-05-12 07:00:00');

        $rule->delete();

        $row = (new ReminderRow(user: $user, occurrence: $occurrence->fresh()))->get();

        $this->assertSame('', $row['cadence']);
        $this->assertSame('calendar', $row['icon']);
        $this->assertNotSame('', $row['text']);
    }

    private function ruleAbout(User $user, ?Contact $contact = null, ?Field $field = null): Reminder
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return $this->reminderAbout(
            user: $user,
            contact: $contact ?? Contact::factory()->create(['vault_id' => $vault->id]),
            field: $field,
        );
    }

    private function occurrenceFor(Reminder $rule, string $instant): ReminderOccurrence
    {
        $due = CarbonImmutable::parse($instant, 'UTC');

        return ReminderOccurrence::factory()->for($rule)->create([
            'source_local_date' => $due->toDateString(),
            'generation_key' => '1:'.$due->toDateString().':on::',
            'scheduled_for' => $due,
            'original_scheduled_for' => $due,
            'local_date' => $due->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(User $user, Reminder $rule, string $instant): array
    {
        return (new ReminderRow(
            user: $user,
            occurrence: $this->occurrenceFor($rule, $instant),
        ))->get();
    }
}
