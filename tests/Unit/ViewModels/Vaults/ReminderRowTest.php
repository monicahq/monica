<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DefaultFieldEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Notification;
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

        $this->assertSame('12', $row->day());
        $this->assertSame('2027', $row->year());
        $this->assertSame('Every year', $row->cadence());
        $this->assertSame('calendar-repeat', $row->cadenceIcon());
        $this->assertSame($contact->name, $row->contactName());
        $this->assertSame(route('contacts.show', ['vault' => $vault, 'contact' => $contact]), $row->contactUrl());
        $this->assertNull($row->avatarUrl());
        $this->assertFalse($row->isLate());
    }

    #[Test]
    public function it_says_a_rule_that_fires_once_fires_once(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $rule->cadence = ReminderCadenceEnum::Once;
        $rule->save();

        $row = $this->rowFor($user, $rule, '2027-05-12 07:00:00');

        $this->assertSame('One time', $row->cadence());
        $this->assertSame('calendar', $row->cadenceIcon());
    }

    #[Test]
    public function it_counts_the_years_of_a_rhythm_somebody_set_themselves(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $rule->frequency_interval = 3;
        $rule->save();

        $this->assertSame('Every 3 years', $this->rowFor($user, $rule, '2027-05-12 07:00:00')->cadence());
    }

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

        $this->assertStringContainsString('09:00', $row->exact());
        $this->assertStringContainsString('Europe/Paris', $row->exact());
        $this->assertStringContainsString('UTC+02:00', $row->exact());
        $this->assertStringContainsString('Will be sent', $row->exact());
    }

    #[Test]
    public function it_words_a_deadline_nobody_dealt_with_as_already_gone(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);

        $row = $this->rowFor($user, $rule, CarbonImmutable::now()->subDays(4)->toDateTimeString());

        $this->assertTrue($row->isLate());
        $this->assertStringContainsString('Sent', $row->exact());
        $this->assertStringNotContainsString('Will be sent', $row->exact());
        $this->assertStringContainsString('ago', $row->relative());
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

        $this->assertSame('gift', $ofBirthday->icon());
        $this->assertSame('calendar', $ofInvented->icon());
    }

    #[Test]
    public function it_still_draws_a_row_whose_rule_has_been_taken_away(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $occurrence = $this->occurrenceFor($rule, '2027-05-12 07:00:00');

        $rule->delete();

        $row = new ReminderRow(user: $user, occurrence: $occurrence->fresh());

        $this->assertSame('', $row->cadence());
        $this->assertSame('calendar', $row->icon());
        $this->assertNotSame('', $row->text());
    }

    #[Test]
    public function it_names_every_channel_that_carried_the_deadline(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $rule = $this->ruleAbout($user);
        $occurrence = $this->occurrenceFor($rule, '2027-03-01 09:00:00');

        foreach (NotificationChannelEnum::cases() as $channel) {
            Notification::factory()->create([
                'account_id' => $rule->account_id,
                'user_id' => $user->id,
                'channel' => $channel,
                'notifiable_type' => ReminderOccurrence::class,
                'notifiable_id' => $occurrence->id,
                'sent_at' => now(),
            ]);
        }

        $row = new ReminderRow(user: $user, occurrence: $occurrence->refresh());

        $this->assertSame(
            ['In the app', 'Email', 'Telegram', 'Text message'],
            $row->channels(),
        );
    }

    #[Test]
    public function it_offers_only_i_remembered_on_the_day_itself(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 15:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);

        $row = $this->rowFor($user, $this->ruleAbout($user), '2026-09-26 09:00:00');

        $this->assertTrue($row->canRemember());
        $this->assertFalse($row->canForget());
        $this->assertFalse($row->isOverdue());
    }

    #[Test]
    public function it_offers_both_answers_from_the_day_after(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-27 08:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);

        $row = $this->rowFor($user, $this->ruleAbout($user), '2026-09-26 09:00:00');

        $this->assertTrue($row->canRemember());
        $this->assertTrue($row->canForget());
        $this->assertTrue($row->isOverdue());
    }

    #[Test]
    public function it_offers_no_answer_before_the_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-25 22:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);

        $row = $this->rowFor($user, $this->ruleAbout($user), '2026-09-26 09:00:00');

        $this->assertFalse($row->canRemember());
        $this->assertFalse($row->canForget());
    }

    #[Test]
    public function it_offers_nothing_once_a_date_is_answered(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 08:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $occurrence = $this->occurrenceFor($this->ruleAbout($user), '2026-09-26 09:00:00');
        $occurrence->update(['answer' => ReminderAnswerEnum::Forgotten, 'answered_at' => now()]);

        $row = new ReminderRow(user: $user, occurrence: $occurrence->refresh());

        $this->assertFalse($row->canRemember());
        $this->assertFalse($row->canForget());
        $this->assertFalse($row->isOverdue());
    }

    #[Test]
    public function it_says_what_became_of_a_date(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);

        $remembered = ReminderOccurrence::factory()->for($rule)->past()->remembered()->create();
        $forgotten = ReminderOccurrence::factory()->for($rule)->daysFromNow(-10)->forgotten()->create();
        $cancelled = ReminderOccurrence::factory()->for($rule)->daysFromNow(-20)->cancelled()->create();
        $open = ReminderOccurrence::factory()->for($rule)->daysFromNow(-30)->create();

        $said = fn (ReminderOccurrence $occurrence): array => [
            new ReminderRow(user: $user, occurrence: $occurrence)->outcome(),
            new ReminderRow(user: $user, occurrence: $occurrence)->tone(),
        ];

        $this->assertSame(['You remembered', 'success'], $said($remembered));
        $this->assertSame(['You forgot', 'neutral'], $said($forgotten));
        $this->assertSame(['Cancelled', 'neutral'], $said($cancelled));
        $this->assertSame(['No answer yet', 'warning'], $said($open));
    }

    #[Test]
    public function it_names_the_day_relative_to_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC', 'date_format' => DateFormatEnum::MonthSpelled]);
        $rule = $this->ruleAbout($user);

        $this->assertStringEndsWith('· today', $this->rowFor($user, $rule, '2026-09-26 09:00:00')->whenLine());
        $this->assertStringEndsWith('· tomorrow', $this->rowFor($user, $rule, '2026-09-27 09:00:00')->whenLine());
        $this->assertStringEndsWith('· yesterday', $this->rowFor($user, $rule, '2026-09-25 09:00:00')->whenLine());
        $this->assertStringEndsWith('· 2 weeks from now', $this->rowFor($user, $rule, '2026-10-10 09:00:00')->whenLine());
    }

    #[Test]
    public function it_edits_a_written_reminder_on_its_own_form_and_offers_no_form_for_a_date_reminder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $manual = Reminder::factory()->manual($contact, now()->addDays(3)->toDateString())->create();
        $fromADate = $this->ruleAbout($user, $contact);

        $manualRow = new ReminderRow(user: $user, occurrence: ReminderOccurrence::factory()->for($manual)->create());
        $dateRow = new ReminderRow(user: $user, occurrence: ReminderOccurrence::factory()->for($fromADate)->create());

        $this->assertSame(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $contact, 'reminder' => $manual]), $manualRow->editUrl());
        $this->assertNull($dateRow->editUrl());
        $this->assertNull($manualRow->sourceLabel());
        $this->assertNotNull($dateRow->sourceLabel());
    }

    #[Test]
    public function it_offers_neither_edit_nor_delete_for_a_deleted_reminder(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);
        $occurrence = ReminderOccurrence::factory()->for($rule)->past()->remembered()->create();
        $rule->delete();

        $row = new ReminderRow(user: $user, occurrence: $occurrence->setRelation('reminder', Reminder::withTrashed()->find($rule->id)));

        $this->assertNull($row->editUrl());
        $this->assertNull($row->deleteUrl());
        $this->assertNull($row->reminderUrl());
    }

    #[Test]
    public function it_offers_no_test_for_a_date_not_generated_yet(): void
    {
        $user = User::factory()->create();
        $rule = $this->ruleAbout($user);

        $unsaved = new ReminderOccurrence([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'reminder_id' => $rule->id,
            'local_date' => now()->addMonths(8)->toDateString(),
            'scheduled_for' => now()->addMonths(8),
        ]);

        $row = new ReminderRow(user: $user, occurrence: $unsaved->setRelation('reminder', $rule));

        $this->assertNull($row->testUrl());
        $this->assertFalse($row->canRemember());
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
            'generation_key' => '1:'.$due->toDateString(),
            'scheduled_for' => $due,
            'local_date' => $due->toDateString(),
        ]);
    }

    private function rowFor(User $user, Reminder $rule, string $instant): ReminderRow
    {
        return new ReminderRow(
            user: $user,
            occurrence: $this->occurrenceFor($rule, $instant),
        );
    }
}
