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

        $rendered->assertSee('Add a reminder', escape: false);
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
    public function it_asks_for_the_wording_the_cadence_the_moments_the_hour_and_the_zone(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('What it should say', escape: false);
        $rendered->assertSee('How often', escape: false);
        $rendered->assertSee('Every time it comes round', escape: false);
        $rendered->assertSee('Comes back', escape: false);
        $rendered->assertSee('When to be told', escape: false);
        $rendered->assertSee('Local time', escape: false);
        $rendered->assertSee('Time zone', escape: false);
        $rendered->assertSee('Add another moment', escape: false);
    }

    #[Test]
    public function the_cadence_stays_in_the_flow_whether_the_rule_comes_back_or_not(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('Comes back', escape: false);
        $rendered->assertSee('x-bind:disabled="! recurs"', escape: false);
        $rendered->assertDontSee('x-show="recurs"', escape: false);
    }

    #[Test]
    public function it_draws_the_cadence_inert_for_a_rule_that_fires_once(): void
    {
        $dimmed = 'duration-(--duration-fast) opacity-55';

        $this->render($this->rule(['cadence' => ReminderCadenceEnum::Once]))
            ->assertSee($dimmed, escape: false);

        $this->render($this->rule(['cadence' => ReminderCadenceEnum::Yearly]))
            ->assertDontSee($dimmed, escape: false);
    }

    #[Test]
    public function every_moment_carries_a_direction_an_amount_and_a_unit(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('[moments][0][direction]', escape: false);
        $rendered->assertSee('[moments][0][amount]', escape: false);
        $rendered->assertSee('[moments][0][unit]', escape: false);
        $rendered->assertSee('On the day', escape: false);
        $rendered->assertSee('Before', escape: false);
        $rendered->assertSee('After', escape: false);
    }

    #[Test]
    public function it_draws_blank_moments_the_page_reveals_one_at_a_time(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('[moments][3][direction]', escape: false);
        $rendered->assertSee('x-on:click="shown++"', escape: false);
        $rendered->assertDontSee('[moments][4][direction]', escape: false);
    }

    #[Test]
    public function a_moment_can_be_removed_and_never_the_last_one(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('Remove this moment', escape: false);
        $rendered->assertSee('x-show="active > 1"', escape: false);
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
            'moments' => [['direction' => 'before', 'amount' => 3, 'unit' => 'weeks']],
        ]));

        $rendered->assertSee('value="Ring Michael"', escape: false);
        $rendered->assertSee('value="18:30"', escape: false);
        $rendered->assertSee('value="4"', escape: false);
        $rendered->assertSee('3 weeks before', escape: false);
        $rendered->assertSee('Asia/Tokyo', escape: false);
    }

    #[Test]
    public function it_summarises_the_rule_in_one_sentence_and_says_when_it_next_falls(): void
    {
        $rendered = $this->render($this->rule([
            'cadence' => ReminderCadenceEnum::Yearly,
            'time_of_day' => '09:00:00',
            'timezone' => 'Europe/Paris',
            'moments' => [['direction' => 'before', 'amount' => 7, 'unit' => 'days']],
        ]));

        $rendered->assertSee('You will be reminded 7 days before, every year, at 09:00 Europe/Paris.', escape: false);
        $rendered->assertSee('Next:', escape: false);
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

        $rendered->assertSee('quiet because this record is archived', escape: false);
    }

    #[Test]
    public function it_says_a_quiet_rule_lost_the_date_it_read(): void
    {
        $rendered = $this->render($this->rule([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared,
        ]));

        $rendered->assertSee('quiet because the date it read is gone', escape: false);
    }

    #[Test]
    public function it_offers_the_wording_the_field_and_the_record_already_say(): void
    {
        $user = User::factory()->create();
        $value = $this->dateOf($user);

        $rendered = $this->panel($user, $value, null);

        $rendered->assertSee("Birthday — {$value->valuable->name}", escape: false);
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
        )->get();

        return $this->blade(
            '<x-field-reminder :field="$field" :reminder="$reminder" name="reminders[7]" id="field" />',
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
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
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
