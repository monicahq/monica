<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderPauseControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_pauses_a_reminder_and_cancels_what_was_ahead(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule);

        $response = $this->actingAs($user)->put($this->pauseUrl($vault, $rule));

        $response->assertRedirect(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('reminders', [
            'id' => $rule->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::PausedByUser->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $occurrence->id,
            'status' => ReminderOccurrenceStatusEnum::Cancelled->value,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleDisabled->value,
        ]);
    }

    #[Test]
    public function it_starts_a_paused_reminder_again_and_brings_back_what_it_cancelled(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule);

        $this->actingAs($user)->put($this->pauseUrl($vault, $rule));
        $response = $this->actingAs($user)->delete($this->pauseUrl($vault, $rule));

        $response->assertRedirect(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $this->assertDatabaseHas('reminders', [
            'id' => $rule->id,
            'is_active' => true,
            'inactive_reason' => null,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $occurrence->id,
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
            'cancelled_reason' => null,
        ]);
    }

    #[Test]
    public function it_refuses_to_start_again_a_reminder_the_system_silenced(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $rule->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete($this->pauseUrl($vault, $rule));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('reminders', [
            'id' => $rule->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived->value,
        ]);
    }

    #[Test]
    public function the_screen_offers_pausing_while_a_reminder_is_firing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee($this->pauseUrl($vault, $rule), escape: false);
        $response->assertSee('Pause');
    }

    #[Test]
    public function the_screen_offers_starting_again_only_what_somebody_paused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $paused = $this->ruleAbout($user, $vault);
        $this->actingAs($user)->put($this->pauseUrl($vault, $paused));

        $archived = $this->ruleAbout($user, $vault);
        $archived->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $paused]))
            ->assertSee('Start it again');

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $archived]))
            ->assertDontSee('Start it again');
    }

    #[Test]
    public function a_reminder_of_another_account_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $stranger = User::factory()->create();
        $theirVault = Vault::factory()->create(['account_id' => $stranger->account_id]);
        $theirs = $this->ruleAbout($stranger, $theirVault);

        $this->actingAs($user)->put($this->pauseUrl($vault, $theirs))->assertStatus(404);
    }

    #[Test]
    public function a_reminder_of_another_vault_of_the_same_account_is_not_found(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $there);

        $this->actingAs($user)->put($this->pauseUrl($here, $rule))->assertStatus(404);
    }

    #[Test]
    public function a_visitor_is_sent_to_sign_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $this->put($this->pauseUrl($vault, $rule))->assertRedirect(route('auth.login.new'));
    }

    private function pauseUrl(Vault $vault, Reminder $reminder): string
    {
        return route('reminders.pause.update', ['vault' => $vault, 'reminder' => $reminder]);
    }

    private function ruleAbout(User $user, Vault $vault): Reminder
    {
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
        ]);

        return $this->reminderAbout(user: $user, contact: $contact);
    }

    private function deadline(Reminder $rule): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
        ]);
    }
}
