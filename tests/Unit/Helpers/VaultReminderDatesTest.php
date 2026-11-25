<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Actions\GenerateReminderOccurrences;
use App\Enums\DefaultFieldEnum;
use App\Enums\ReminderCadenceEnum;
use App\Helpers\VaultReminderDate;
use App\Helpers\VaultReminderDates;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderRow;
use App\ViewModels\Vaults\VaultDeadlines;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultReminderDatesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
    }

    #[Test]
    public function it_lists_the_same_dates_as_the_dashboard(): void
    {
        $stanley = $this->contact('Stanley', 'Hudson');
        $this->manualReminder($stanley, '2026-09-01');
        $this->manualReminder($stanley, '2026-09-04');
        $this->manualReminder($stanley, '2026-09-08');
        $this->manualReminder($stanley, '2026-09-30');

        $dates = $this->page();
        $dashboard = new VaultDeadlines(user: $this->user, vault: $this->vault)->rowsUntil(CarbonImmutable::parse('2026-09-11', 'UTC'));

        $this->assertSame(
            $dashboard->map(fn (ReminderRow $row): string => $row->occurrence()->local_date->toDateString())->all(),
            $this->days($dates),
        );
        $this->assertSame(['2026-09-01', '2026-09-04', '2026-09-08'], $this->days($dates));
    }

    #[Test]
    public function it_keeps_one_category(): void
    {
        $stanley = $this->contact('Stanley', 'Hudson');
        $this->manualReminder($stanley, '2026-09-01');
        $this->manualReminder($stanley, '2026-09-04');
        $this->manualReminder($stanley, '2026-09-08');

        $this->assertSame(['2026-09-01'], $this->days($this->page(category: 'overdue')));
        $this->assertSame(['2026-09-04'], $this->days($this->page(category: 'today')));
        $this->assertSame(['2026-09-08'], $this->days($this->page(category: 'upcoming')));
    }

    #[Test]
    public function it_reaches_further_with_more_days(): void
    {
        $this->manualReminder($this->contact('Stanley', 'Hudson'), '2026-09-24');

        $this->assertSame([], $this->days($this->page()));
        $this->assertSame(['2026-09-24'], $this->days($this->page(days: 30)));
    }

    #[Test]
    public function it_lists_the_reminders_about_a_group(): void
    {
        $group = Group::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $this->vault->id, 'name' => 'Party Planning Committee']);
        $this->manualReminder($group, '2026-09-06');

        $dates = $this->page();

        $this->assertCount(1, $dates->items());
        $this->assertTrue($group->is($dates->items()[0]->subject));
    }

    #[Test]
    public function it_leaves_out_the_dates_of_another_vault(): void
    {
        $annex = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $this->user->account_id, 'vault_id' => $annex->id, 'first_name' => 'Toby']);
        $this->manualReminder($toby, '2026-09-06');

        $this->assertSame([], $this->days($this->page()));
    }

    #[Test]
    public function it_lists_the_answered_dates_newest_answer_first_in_the_history(): void
    {
        $stanley = $this->contact('Stanley', 'Hudson');
        $rule = Reminder::factory()->manual($stanley, '2026-08-01')->create(['timezone' => 'UTC']);
        $older = ReminderOccurrence::factory()->daysFromNow(-20)->forgotten()->create(['reminder_id' => $rule->id, 'answered_at' => now()->subDays(10)]);
        $newer = ReminderOccurrence::factory()->daysFromNow(-10)->remembered()->create(['reminder_id' => $rule->id, 'answered_at' => now()->subDay()]);
        ReminderOccurrence::factory()->daysFromNow(-5)->create(['reminder_id' => $rule->id]);

        $dates = $this->page(category: 'history');

        $this->assertSame([$newer->id, $older->id], array_map(fn (VaultReminderDate $date): int => $date->occurrence->id, $dates->items()));
    }

    #[Test]
    public function it_gives_the_age_reached_on_a_birthday_reminder(): void
    {
        $michael = $this->contact('Michael', 'Scott');
        $this->birthdayReminder($michael, '1964-09-07');

        $dates = $this->page(kind: VaultReminderDates::BIRTHDAYS);

        $this->assertSame(['2026-09-07'], $this->days($dates));
        $this->assertSame(62, $dates->items()[0]->age);
        $this->assertNotNull($dates->items()[0]->occurrence);
    }

    #[Test]
    public function it_gives_no_age_when_the_year_of_birth_is_unknown(): void
    {
        $michael = $this->contact('Michael', 'Scott');
        $this->birthday($michael, '1900-09-07', yearIsUnknown: true);

        $dates = $this->page(kind: VaultReminderDates::BIRTHDAYS);

        $this->assertSame(['2026-09-07'], $this->days($dates));
        $this->assertNull($dates->items()[0]->age);
    }

    #[Test]
    public function it_keeps_only_the_birthdays_when_asked(): void
    {
        $this->manualReminder($this->contact('Stanley', 'Hudson'), '2026-09-06');
        $this->birthdayReminder($this->contact('Michael', 'Scott'), '1964-09-07');

        $this->assertSame(['2026-09-07'], $this->days($this->page(kind: VaultReminderDates::BIRTHDAYS)));
    }

    #[Test]
    public function it_lists_a_birthday_nobody_asked_to_be_reminded_of(): void
    {
        $dwight = $this->contact('Dwight', 'Schrute');
        $this->birthday($dwight, '1974-09-06');

        $dates = $this->page(kind: VaultReminderDates::BIRTHDAYS);

        $this->assertCount(1, $dates->items());
        $this->assertNull($dates->items()[0]->occurrence);
        $this->assertTrue($dwight->is($dates->items()[0]->subject));
        $this->assertSame('2026-09-06', $dates->items()[0]->date->toDateString());
        $this->assertSame(52, $dates->items()[0]->age);
    }

    #[Test]
    public function it_lists_a_birthday_with_a_reminder_once(): void
    {
        $this->birthdayReminder($this->contact('Michael', 'Scott'), '1964-09-07');

        $this->assertCount(1, $this->page(kind: VaultReminderDates::BIRTHDAYS)->items());
    }

    #[Test]
    public function it_leaves_out_a_birthday_without_a_reminder_that_has_passed(): void
    {
        $this->birthday($this->contact('Dwight', 'Schrute'), '1974-09-03');

        $this->assertSame([], $this->days($this->page(kind: VaultReminderDates::BIRTHDAYS)));
        $this->assertSame([], $this->days($this->page(category: 'history', kind: VaultReminderDates::BIRTHDAYS)));
    }

    #[Test]
    public function it_leaves_out_birthdays_without_a_reminder_from_every_reminder(): void
    {
        $this->birthday($this->contact('Dwight', 'Schrute'), '1974-09-06');

        $this->assertSame([], $this->days($this->page()));
    }

    #[Test]
    public function it_hides_a_sensitive_birthday_from_a_key_that_may_not_read_it(): void
    {
        $dwight = $this->contact('Dwight', 'Schrute');
        $this->birthday($dwight, '1974-09-06', sensitive: true);
        $michael = $this->contact('Michael', 'Scott');
        $this->birthdayReminder($michael, '1964-09-07', sensitive: true);

        $hidden = $this->page(kind: VaultReminderDates::BIRTHDAYS);
        $shown = $this->page(kind: VaultReminderDates::BIRTHDAYS, reachesSensitiveFields: true);

        $this->assertSame(['2026-09-07'], $this->days($hidden));
        $this->assertNull($hidden->items()[0]->age);
        $this->assertSame(['2026-09-06', '2026-09-07'], $this->days($shown));
        $this->assertSame(62, $shown->items()[1]->age);
    }

    /** @return LengthAwarePaginator<int, VaultReminderDate> */
    private function page(?string $category = null, string $kind = VaultReminderDates::ALL, int $days = 7, bool $reachesSensitiveFields = false): LengthAwarePaginator
    {
        return VaultReminderDates::page(
            user: $this->user,
            vault: $this->vault,
            category: $category,
            kind: $kind,
            days: $days,
            reachesSensitiveFields: $reachesSensitiveFields,
            perPage: 50,
            page: 1,
        );
    }

    /**
     * @param  LengthAwarePaginator<int, VaultReminderDate>  $dates
     * @return list<string>
     */
    private function days(LengthAwarePaginator $dates): array
    {
        return array_values(array_map(fn (VaultReminderDate $date): string => $date->date->toDateString(), $dates->items()));
    }

    private function contact(string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function manualReminder(Contact|Group $record, string $date): Reminder
    {
        $rule = Reminder::factory()->manual($record, $date)->once()->create(['timezone' => 'UTC']);
        ReminderOccurrence::factory()->create([
            'reminder_id' => $rule->id,
            'local_date' => $date,
            'generation_key' => "1:{$date}",
            'scheduled_for' => CarbonImmutable::parse("{$date} 09:00:00", 'UTC'),
        ]);

        return $rule;
    }

    private function birthday(Contact $contact, string $date, bool $yearIsUnknown = false, bool $sensitive = false): FieldValue
    {
        $field = Field::factory()->recurringDate()->create(['key' => DefaultFieldEnum::Birthday->value, 'is_sensitive' => $sensitive]);

        return FieldValue::factory()->recurringDate($date)->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
            'year_is_unknown' => $yearIsUnknown,
        ]);
    }

    private function birthdayReminder(Contact $contact, string $date, bool $sensitive = false): Reminder
    {
        $birthday = $this->birthday($contact, $date, sensitive: $sensitive);

        $rule = Reminder::factory()->create([
            'field_value_id' => $birthday->id,
            'cadence' => ReminderCadenceEnum::Yearly,
            'timezone' => 'UTC',
        ]);
        new GenerateReminderOccurrences(reminder: $rule)->execute();

        return $rule;
    }
}
