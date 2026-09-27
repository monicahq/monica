<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_titled_by_the_reminder(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        $this->assertSame($reminder->name, $this->viewModel($user, $reminder)->title());
    }

    #[Test]
    public function it_says_an_active_reminder_is_active(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        $state = $this->viewModel($user, $reminder)->state();

        $this->assertTrue($state['isActive']);
        $this->assertSame('success', $state['tone']);
        $this->assertNotSame('', $state['label']);
        $this->assertNull($state['quietBecause']);
    }

    #[Test]
    public function it_explains_why_a_reminder_has_gone_quiet(): void
    {
        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);
        $reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $state = $this->viewModel($user, $reminder->refresh())->state();

        $this->assertFalse($state['isActive']);
        $this->assertNotNull($state['quietBecause']);
        $this->assertSame('Stopped', $state['label']);
    }

    #[Test]
    public function it_describes_the_schedule(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        $schedule = $this->viewModel($user, $reminder)->schedule();

        $labels = array_column($schedule['fields'], 'value');

        $this->assertContains('Europe/Paris', $labels);
        $this->assertContains('every year', $labels);
        $this->assertCount(4, $schedule['fields']);
    }

    #[Test]
    public function it_names_the_reader_s_zone_only_when_the_rule_was_frozen_in_another(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create(['timezone' => 'Europe/Paris']));

        $this->assertSame([null, null, null, null], array_column($this->viewModel($user, $reminder)->schedule()['fields'], 'note'));

        $user->update(['timezone' => 'America/New_York']);

        $this->assertSame(
            [null, null, null, 'Fixed when the reminder was made. Yours is America/New_York.'],
            array_column($this->viewModel($user, $reminder)->schedule()['fields'], 'note'),
        );
    }

    #[Test]
    public function it_says_a_rule_that_fires_once_fires_once(): void
    {
        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);
        $reminder->update(['cadence' => ReminderCadenceEnum::Once]);

        $this->assertContains(
            'once and no more',
            array_column($this->viewModel($user, $reminder->refresh())->schedule()['fields'], 'value'),
        );
    }

    #[Test]
    public function it_reads_the_next_date_in_the_zone_frozen_on_the_rule(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        $next = $this->viewModel($user, $reminder)->next();

        $this->assertStringContainsString('Europe/Paris', $next['note']);
        $this->assertNotSame('', $next['date']);
    }

    #[Test]
    public function it_claims_no_next_date_for_a_reminder_that_has_gone_quiet(): void
    {
        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);
        $reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $next = $this->viewModel($user, $reminder->refresh())->next();

        $this->assertFalse($next['isScheduled']);
    }

    #[Test]
    public function it_draws_the_contact_the_reminder_is_about(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = $this->reminderAbout($user, $contact);

        $card = $this->viewModel($user, $reminder)->contact();

        $this->assertNotNull($card);
        $this->assertSame($contact->name, $card['name']);
        $this->assertStringContainsString((string) $contact->slug, $card['url']);
        $this->assertSame(1, $card['reminderCount']);
    }

    #[Test]
    public function it_counts_every_reminder_the_contact_carries(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $this->reminderAbout($user, $contact);
        $reminder = $this->reminderAbout($user, $contact);

        $this->assertSame(2, $this->viewModel($user, $reminder)->contact()['reminderCount']);
    }

    #[Test]
    public function it_points_at_the_contact_for_editing(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = $this->reminderAbout($user, $contact);

        $this->assertStringContainsString(
            (string) $contact->slug,
            $this->viewModel($user, $reminder)->editUrl(),
        );
    }

    #[Test]
    public function it_lists_the_occurrences_still_ahead(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());
        ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        $this->assertCount(1, $this->viewModel($user, $reminder)->occurrences());
    }

    #[Test]
    public function it_leaves_out_an_occurrence_that_has_been_cancelled(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());
        ReminderOccurrence::factory()->cancelled()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        $this->assertSame([], $this->viewModel($user, $reminder)->occurrences());
    }

    #[Test]
    public function it_offers_the_answers_on_the_next_date_while_it_waits(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $reminder = $this->reminderAbout($user);
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-2)->create();

        $next = $this->viewModel($user, $reminder)->next();

        $this->assertSame('Overdue, waiting for your answer', $next['overline']);
        $this->assertTrue($next['row']->canForget());
    }

    #[Test]
    public function it_names_a_date_due_today_and_a_date_still_ahead(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);

        $today = $this->reminderAbout($user);
        ReminderOccurrence::factory()->for($today)->today()->create();

        $ahead = $this->reminderAbout($user);
        ReminderOccurrence::factory()->for($ahead)->daysFromNow(9)->create();

        $this->assertSame('Today', $this->viewModel($user, $today)->next()['overline']);
        $this->assertSame('Next', $this->viewModel($user, $ahead)->next()['overline']);
        $this->assertFalse($this->viewModel($user, $ahead)->next()['row']->canRemember());
    }

    #[Test]
    public function it_lists_every_past_date_with_its_answer_newest_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $reminder = $this->reminderAbout($user);

        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-365)->forgotten()->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-30)->remembered()->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-1)->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(20)->create();

        $answers = $this->viewModel($user, $reminder)->answers();

        $this->assertSame(['No answer yet', 'You remembered', 'You forgot'], array_column($answers, 'outcome'));
        $this->assertNull($answers[0]['answeredOn']);
        $this->assertNotNull($answers[1]['answeredOn']);
    }

    #[Test]
    public function it_edits_a_written_reminder_on_its_own_form(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        $this->assertSame(
            route('contacts.reminders.edit', ['vault' => $reminder->vault, 'contact' => $contact, 'reminder' => $reminder]),
            $this->viewModel($user, $reminder)->editUrl(),
        );
    }

    private function viewModel(User $user, Reminder $reminder): ReminderViewModel
    {
        return new ReminderViewModel(
            user: $user,
            vault: Vault::query()->findOrFail($reminder->vault_id),
            reminder: $reminder,
        );
    }
}
