<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRemindersPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $michael;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->michael = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }

    #[Test]
    public function it_lists_a_deadline_with_its_answers_and_the_way_to_every_reminder(): void
    {
        $deadline = $this->deadline('Return the Dundie to Pam', '2026-09-20');

        $this->overview()
            ->assertSee('Return the Dundie to Pam')
            ->assertSee(route('reminders.occurrences.answer.update', ['vault' => $this->vault, 'reminderOccurrence' => $deadline]))
            ->assertSee('value="remembered"', false)
            ->assertSee('value="forgotten"', false)
            ->assertSee('All of Michael’s reminders')
            ->assertSee(route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->michael]))
            ->assertDontSee('Nothing to remember about Michael yet');
    }

    #[Test]
    public function it_offers_the_four_filters_as_tabs_over_one_list(): void
    {
        $this->deadline('Return the Dundie to Pam', '2026-09-20');

        $this->overview()
            ->assertSee('id="contact-reminder-panel"', false)
            ->assertSee('\u0022filter\u0022:\u0022all\u0022', false)
            ->assertSee('role="tablist"', false)
            ->assertSeeInOrder(['id="contact-deadlines-tab-all"', 'id="contact-deadlines-tab-overdue"', 'id="contact-deadlines-tab-today"', 'id="contact-deadlines-tab-upcoming"'], false)
            ->assertSee('aria-controls="contact-deadlines-list"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee('Nothing today');
    }

    #[Test]
    public function a_contact_with_nothing_to_remember_gets_the_blank_panel(): void
    {
        $this->overview()
            ->assertSee('Nothing to remember about Michael yet')
            ->assertSee('Ask to be told about a date on their page, such as a birthday.')
            ->assertDontSee('id="contact-deadlines-list"', false)
            ->assertDontSee('All of Michael’s reminders');
    }

    #[Test]
    public function add_unfolds_a_form_that_writes_the_reminder_and_redraws_the_panel_alone(): void
    {
        $this->overview()
            ->assertSee('aria-controls="contact-reminder-form"', false)
            ->assertSee('id="contact-reminder-form"', false)
            ->assertSee('action="'.route('contacts.reminders.create', ['vault' => $this->vault, 'contact' => $this->michael]).'"', false)
            ->assertSee('x-target="contact-reminder-panel"', false)
            ->assertSee('name="return_to" value="contact"', false)
            ->assertSee('id="contact-reminder-panel" x-merge="replace"', false)
            ->assertSee('About Michael Scott')
            ->assertSee('What should we remind you about?')
            ->assertSee('name="name"', false)
            ->assertSee('name="starts_on"', false)
            ->assertSee('name="cadence"', false)
            ->assertSee('name="interval"', false)
            ->assertSee('x-data="reminderInterval(', false)
            ->assertSee('Add reminder');
    }

    #[Test]
    public function the_notification_time_folds_out_with_the_user_s_own_defaults(): void
    {
        $this->user->update(['timezone' => 'America/Toronto']);

        $this->overview()
            ->assertSee('aria-controls="contact-reminder-time"', false)
            ->assertSee('name="time"', false)
            ->assertSee('value="09:00"', false)
            ->assertSee('<option value="America/Toronto" selected', false)
            ->assertSee('We’ll notify you on this date. No advance notifications.');
    }

    #[Test]
    public function a_refused_reminder_comes_back_with_the_form_open_and_its_error(): void
    {
        $overview = route('contacts.show', ['vault' => $this->vault, 'contact' => $this->michael]);

        $this->actingAs($this->user)
            ->from($overview)
            ->followingRedirects()
            ->post(route('contacts.reminders.create', ['vault' => $this->vault, 'contact' => $this->michael]), [
                'name' => 'Return the Dundie to Pam',
                'starts_on' => '',
                'return_to' => 'contact',
                'cadence' => 'once',
                'time' => '09:00',
                'timezone' => 'UTC',
            ])
            ->assertStatus(200)
            ->assertSee('Pick a date.')
            ->assertSee('value="Return the Dundie to Pam"', false)
            ->assertSee('\u0022adding\u0022:true', false);
    }

    #[Test]
    public function an_archived_contact_is_offered_no_way_to_add_a_reminder(): void
    {
        $this->michael->update(['is_archived' => true]);

        $this->overview()
            ->assertDontSee('id="contact-reminder-form"', false)
            ->assertDontSee(route('contacts.reminders.new', ['vault' => $this->vault, 'contact' => $this->michael]));
    }

    private function overview(): TestResponse
    {
        return $this->actingAs($this->user)
            ->get(route('contacts.show', ['vault' => $this->vault, 'contact' => $this->michael]))
            ->assertStatus(200);
    }

    private function deadline(string $name, string $day): ReminderOccurrence
    {
        $rule = Reminder::factory()->manual($this->michael, $day)->once()->create([
            'name' => $name,
            'timezone' => 'UTC',
        ]);

        return ReminderOccurrence::factory()->for($rule)->create([
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
        ]);
    }
}
