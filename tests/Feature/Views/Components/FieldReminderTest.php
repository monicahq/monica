<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use App\ViewModels\Vaults\ContactReminderBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_the_question_with_a_box_that_says_what_it_opens(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('Remind me about this date', escape: false);
        $rendered->assertSee('name="reminders[', escape: false);
        $rendered->assertSee('aria-controls="field-panel"', escape: false);
        $rendered->assertSee(':aria-expanded="on"', escape: false);
        $rendered->assertSee('id="field-panel"', escape: false);
    }

    #[Test]
    public function the_panel_is_closed_until_the_box_is_ticked(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('x-show="on"', escape: false);
        $rendered->assertSee('x-cloak', escape: false);
        $rendered->assertDontSee('x-bind:hidden', escape: false);
    }

    #[Test]
    public function it_asks_how_often_at_what_hour_in_which_zone_and_what_it_will_say(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('Repeats', escape: false);
        $rendered->assertSee('name="reminders[7][repeat]"', escape: false);
        $rendered->assertSee('At', escape: false);
        $rendered->assertSee('Time zone', escape: false);
        $rendered->assertSee('It will say', escape: false);
        $rendered->assertSee('You will be notified on the day. The time zone is yours by default.', escape: false);
        $rendered->assertDontSee('[moments]', escape: false);
    }

    #[Test]
    public function it_offers_once_and_every_so_many_years(): void
    {
        $rendered = $this->render();

        $rendered->assertSeeInOrder(['Only once', 'Every year', 'Every 2 years', 'Every 5 years', 'Every 10 years'], escape: false);
        $rendered->assertSee('<option value="yearly:1" selected', escape: false);
    }

    #[Test]
    public function it_keeps_offering_a_rhythm_the_list_no_longer_suggests_to_the_reminder_that_has_it(): void
    {
        $rendered = $this->render($this->rule([
            'cadence' => ReminderCadenceEnum::Monthly,
            'frequency_interval' => 4,
        ]));

        $rendered->assertSee('<option value="monthly:4" selected', escape: false);
        $rendered->assertSee('Every 4 months', escape: false);
    }

    #[Test]
    public function it_reads_a_saved_rule_back_into_every_control(): void
    {
        $rendered = $this->render($this->rule([
            'name' => 'Ring Michael',
            'cadence' => ReminderCadenceEnum::Monthly,
            'frequency_interval' => 4,
            'time_of_day' => '18:30:00',
            'timezone' => 'Asia/Tokyo',
        ]));

        $rendered->assertSee('value="Ring Michael"', escape: false);
        $rendered->assertSee('value="18:30"', escape: false);
        $rendered->assertSee('<option value="monthly:4" selected', escape: false);
        $rendered->assertSee('<option value="Asia/Tokyo" selected', escape: false);
    }

    #[Test]
    public function it_says_when_the_next_reminder_falls_and_at_what_hour(): void
    {
        $this->travelTo('2026-09-27 12:00:00');

        $rendered = $this->render($this->rule([
            'cadence' => ReminderCadenceEnum::Yearly,
            'time_of_day' => '09:00:00',
            'timezone' => 'Europe/Paris',
        ]));

        $rendered->assertSee('Next reminder on', escape: false);
        $rendered->assertSee('February 3, 2027 at 09:00', escape: false);
        $rendered->assertSee('aria-live="polite"', escape: false);
    }

    #[Test]
    public function it_warns_before_a_save_takes_a_reminder_away(): void
    {
        $rendered = $this->render($this->rule());

        $rendered->assertSee('Saving now removes this reminder', escape: false);
        $rendered->assertSee('x-show="! on"', escape: false);
    }

    #[Test]
    public function it_has_nothing_to_warn_about_on_a_date_that_carries_no_reminder(): void
    {
        $this->render()->assertDontSee('Saving now removes this reminder', escape: false);
    }

    #[Test]
    public function it_says_in_words_why_a_rule_has_gone_quiet(): void
    {
        $rendered = $this->render($this->rule([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]));

        $rendered->assertSee('stopped because this record is archived', escape: false);
    }

    #[Test]
    public function it_says_a_quiet_rule_lost_the_date_it_read(): void
    {
        $rendered = $this->render($this->rule([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared,
        ]));

        $rendered->assertSee('stopped because the date it read is gone', escape: false);
    }

    #[Test]
    public function it_offers_the_wording_the_field_and_the_record_already_say(): void
    {
        $user = User::factory()->create();
        $value = $this->dateOf($user);

        $rendered = $this->panel($user, $value, null);

        $rendered->assertSee("Birthday — {$value->valuable->name}", escape: false);
    }

    #[Test]
    public function it_hands_the_panel_to_the_registered_component_rather_than_writing_it_in_an_attribute(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('x-data="fieldReminder(', escape: false);
        $rendered->assertSee('locale', escape: false);
        $rendered->assertSee('dateId', escape: false);
        $rendered->assertDontSee('count === 1', escape: false);
        $rendered->assertDontSee('pair.one', escape: false);
    }

    private function render(?Reminder $reminder = null): TestView
    {
        if ($reminder instanceof Reminder) {
            return $this->panel($reminder->createdBy, $reminder->fieldValue, $reminder);
        }

        $user = User::factory()->create();

        return $this->panel($user, $this->dateOf($user), null);
    }

    private function panel(User $user, FieldValue $value, ?Reminder $reminder): TestView
    {
        $block = new ContactReminderBlock(
            user: $user,
            contact: $value->valuable,
            field: $value->field,
            reminder: $reminder,
        );

        return $this->blade(
            '<x-field-reminder :field="$field" :reminder="$reminder" name="reminders[7]" id="field" date-id="date" />',
            ['field' => $value->field, 'reminder' => $block],
        );
    }

    /** @param  array<string, mixed>  $attributes */
    private function rule(array $attributes = []): Reminder
    {
        $user = User::factory()->create();
        $value = $this->dateOf($user);

        return Reminder::factory()->for($value, 'fieldValue')->create(array_merge([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $value->valuable_id,
            'field_id' => $value->field_id,
            'created_by_user_id' => $user->id,
        ], $attributes));
    }

    private function dateOf(User $user): FieldValue
    {
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        return FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);
    }
}
