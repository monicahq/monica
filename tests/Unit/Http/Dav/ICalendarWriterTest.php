<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Enums\FieldTypeEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Http\Dav\ICalendarWriter;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ICalendarWriterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_recurring_date_as_an_all_day_entry_that_comes_round_every_year(): void
    {
        [$contact, $value] = $this->recurringDate('1984-01-20');

        $written = new ICalendarWriter()->recurringDate($contact, $value);

        $this->assertStringContainsString('BEGIN:VEVENT', $written);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:19840120', $written);
        $this->assertStringContainsString('DTEND;VALUE=DATE:19840121', $written);
        $this->assertStringContainsString('RRULE:FREQ=YEARLY', $written);
        $this->assertStringContainsString('UID:'.$contact->uuid.'-'.$value->id, $written);
        $this->assertStringContainsString('Birthday', $written);
    }

    #[Test]
    public function it_writes_a_date_whose_year_is_unknown_in_this_year(): void
    {
        [$contact, $value] = $this->recurringDate('1900-03-24', yearIsUnknown: true);

        $written = new ICalendarWriter()->recurringDate($contact, $value);

        $this->assertStringContainsString('DTSTART;VALUE=DATE:'.CarbonImmutable::now()->format('Y').'0324', $written);
    }

    #[Test]
    public function it_names_the_entry_after_the_person_and_the_field(): void
    {
        [$contact, $value] = $this->recurringDate('1984-01-20');

        $this->assertStringContainsString(
            $contact->name,
            new ICalendarWriter()->recurringDate($contact, $value),
        );
    }

    #[Test]
    public function it_writes_a_deadline_as_a_task_due_at_the_moment_it_will_act(): void
    {
        $vault = Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'name' => 'Wish Dwight a happy birthday',
        ]);
        $at = CarbonImmutable::parse('2026-10-01 09:00:00');
        $occurrence = ReminderOccurrence::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'reminder_id' => $reminder->id,
            'scheduled_for' => $at,
            'original_scheduled_for' => $at,
            'status' => ReminderOccurrenceStatusEnum::Pending,
        ]);

        $written = new ICalendarWriter()->reminder($occurrence);

        $this->assertStringContainsString('BEGIN:VTODO', $written);
        $this->assertStringContainsString('UID:'.$occurrence->uuid, $written);
        $this->assertStringContainsString('DUE:'.$at->utc()->format('Ymd\THis\Z'), $written);
        $this->assertStringContainsString('Wish Dwight a happy birthday', $written);
        $this->assertStringContainsString($contact->name, $written);
    }

    #[Test]
    public function it_names_a_deadline_after_the_person_when_the_rule_has_no_name(): void
    {
        $vault = Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'name' => '',
        ]);
        $occurrence = ReminderOccurrence::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'reminder_id' => $reminder->id,
            'status' => ReminderOccurrenceStatusEnum::Pending,
        ]);

        $this->assertStringContainsString(
            $contact->name,
            new ICalendarWriter()->reminder($occurrence),
        );
    }

    /** @return array{0: Contact, 1: FieldValue} */
    private function recurringDate(string $date, bool $yearIsUnknown = false): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'template_version_id' => $version->id,
        ]);
        $field = $version->sectionVersions()->with('fields')->get()->flatMap(fn ($section) => $section->fields)->first();

        $value = FieldValue::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => null,
            'value_date' => $date,
            'year_is_unknown' => $yearIsUnknown,
        ]);

        return [$contact, $value];
    }
}
