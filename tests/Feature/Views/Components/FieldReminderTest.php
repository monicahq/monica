<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\ReminderCadenceEnum;
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

        $rendered->assertSee('name="reminders[', escape: false);
        $rendered->assertSee('aria-controls="field-panel"', escape: false);
        $rendered->assertSee(':aria-expanded="on"', escape: false);
        $rendered->assertSee('id="field-panel"', escape: false);
    }

    #[Test]
    public function it_asks_how_often_at_what_hour_in_which_zone_and_what_it_will_say(): void
    {
        $rendered = $this->render();

        $rendered->assertSee('name="reminders[7][repeat]"', escape: false);
        $rendered->assertDontSee('[moments]', escape: false);
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
    public function it_warns_before_a_save_takes_a_reminder_away(): void
    {
        $rendered = $this->render($this->rule());

        $rendered->assertSee('x-show="! on"', escape: false);
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
