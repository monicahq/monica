<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_a_vault_and_a_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->account()->exists());
        $this->assertTrue($occurrence->vault()->exists());
        $this->assertTrue($occurrence->reminder()->exists());
    }

    #[Test]
    public function it_is_reachable_by_its_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->reminder->occurrences()->exists());
    }

    #[Test]
    public function it_keeps_the_two_instants_apart(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->scheduled_for->equalTo($occurrence->original_scheduled_for));
    }

    #[Test]
    public function it_finds_the_deadlines_whose_instant_has_arrived(): void
    {
        $due = ReminderOccurrence::factory()->past()->create();
        $ahead = ReminderOccurrence::factory()->create();

        $found = ReminderOccurrence::query()->due()->pluck('id');

        $this->assertTrue($found->contains($due->id));
        $this->assertFalse($found->contains($ahead->id));
    }

    #[Test]
    public function it_still_delivers_a_deadline_somebody_put_off(): void
    {
        $postponed = ReminderOccurrence::factory()->past()->create([
            'status' => ReminderOccurrenceStatusEnum::Postponed,
        ]);

        $this->assertTrue(ReminderOccurrence::query()->due()->pluck('id')->contains($postponed->id));
    }

    #[Test]
    public function it_does_not_deliver_a_deadline_already_told_about(): void
    {
        $notified = ReminderOccurrence::factory()->past()->notified()->create();

        $this->assertFalse(ReminderOccurrence::query()->due()->pluck('id')->contains($notified->id));
    }

    #[Test]
    public function it_finds_only_the_deadlines_that_have_not_happened_yet(): void
    {
        $ahead = ReminderOccurrence::factory()->create();
        $behind = ReminderOccurrence::factory()->past()->create();

        $found = ReminderOccurrence::query()->stillAhead()->pluck('id');

        $this->assertTrue($found->contains($ahead->id));
        $this->assertFalse($found->contains($behind->id));
    }

    #[Test]
    public function it_says_whether_somebody_may_still_act_on_it(): void
    {
        $pending = ReminderOccurrence::factory()->create();
        $cancelled = ReminderOccurrence::factory()->cancelled()->create();

        $this->assertTrue($pending->isActionable());
        $this->assertFalse($cancelled->isActionable());
    }

    #[Test]
    public function it_refuses_two_deadlines_with_the_same_identity_under_one_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $written = ReminderOccurrence::query()->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'account_id' => $occurrence->account_id,
            'vault_id' => $occurrence->vault_id,
            'reminder_id' => $occurrence->reminder_id,
            'source_local_date' => $occurrence->source_local_date->toDateString(),
            'moment_direction' => $occurrence->moment_direction->value,
            'generation_key' => $occurrence->generation_key,
            'scheduled_for' => $occurrence->scheduled_for,
            'original_scheduled_for' => $occurrence->original_scheduled_for,
            'local_date' => $occurrence->local_date->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $written);
        $this->assertSame(1, ReminderOccurrence::query()->where('reminder_id', $occurrence->reminder_id)->count());
    }

    #[Test]
    public function it_finds_what_one_vault_will_still_remind_somebody_about_in_the_order_it_happens(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $late = $this->deadline($rule, '-3 days');
        $soon = $this->deadline($rule, '+2 days');
        $later = $this->deadline($rule, '+30 days');

        $found = ReminderOccurrence::query()
            ->upcomingInVault(vault: $vault, until: CarbonImmutable::now()->addDays(90))
            ->pluck('id')
            ->all();

        $this->assertSame([$late->id, $soon->id, $later->id], $found);
    }

    #[Test]
    public function it_leaves_out_what_is_beyond_the_window_it_is_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $inside = $this->deadline($rule, '+80 days');
        $beyond = $this->deadline($rule, '+240 days');

        $found = ReminderOccurrence::query()
            ->upcomingInVault(vault: $vault, until: CarbonImmutable::now()->addDays(90))
            ->pluck('id');

        $this->assertTrue($found->contains($inside->id));
        $this->assertFalse($found->contains($beyond->id));
    }

    #[Test]
    public function it_leaves_out_a_deadline_somebody_has_finished_with(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $pending = $this->deadline($rule, '+7 days');
        $dismissed = $this->deadline($rule, '+8 days', ['status' => ReminderOccurrenceStatusEnum::Dismissed]);
        $cancelled = $this->deadline($rule, '+9 days', [
            'status' => ReminderOccurrenceStatusEnum::Cancelled,
            'cancelled_at' => now(),
        ]);

        $found = ReminderOccurrence::query()
            ->upcomingInVault(vault: $vault, until: CarbonImmutable::now()->addDays(90))
            ->pluck('id');

        $this->assertTrue($found->contains($pending->id));
        $this->assertFalse($found->contains($dismissed->id));
        $this->assertFalse($found->contains($cancelled->id));
    }

    #[Test]
    public function it_leaves_out_a_deadline_whose_rule_has_gone_quiet_or_been_taken_away(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $quiet = $this->ruleIn(user: $user, vault: $vault);
        $quiet->is_active = false;
        $quiet->save();
        $ofQuiet = $this->deadline($quiet, '+7 days');

        $removed = $this->ruleIn(user: $user, vault: $vault);
        $ofRemoved = $this->deadline($removed, '+7 days');
        $removed->delete();

        $found = ReminderOccurrence::query()
            ->upcomingInVault(vault: $vault, until: CarbonImmutable::now()->addDays(90))
            ->pluck('id');

        $this->assertFalse($found->contains($ofQuiet->id));
        $this->assertFalse($found->contains($ofRemoved->id));
    }

    #[Test]
    public function it_leaves_out_the_deadlines_of_another_vault_and_of_another_account(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $stranger = User::factory()->create();
        $theirVault = Vault::factory()->create(['account_id' => $stranger->account_id]);

        $ofMine = $this->deadline($this->ruleIn(user: $user, vault: $here), '+7 days');
        $ofOtherVault = $this->deadline($this->ruleIn(user: $user, vault: $elsewhere), '+7 days');
        $ofStranger = $this->deadline($this->ruleIn(user: $stranger, vault: $theirVault), '+7 days');

        $found = ReminderOccurrence::query()
            ->upcomingInVault(vault: $here, until: CarbonImmutable::now()->addDays(90))
            ->pluck('id');

        $this->assertTrue($found->contains($ofMine->id));
        $this->assertFalse($found->contains($ofOtherVault->id));
        $this->assertFalse($found->contains($ofStranger->id));
    }

    #[Test]
    public function it_finds_what_one_vault_has_already_sent_most_recent_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $older = $this->deadline($rule, '-9 days', [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now()->subDays(9),
        ]);
        $newer = $this->deadline($rule, '-1 day', [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now()->subDay(),
        ]);
        $neverSent = $this->deadline($rule, '+7 days');

        $found = ReminderOccurrence::query()->sentInVault(vault: $vault)->pluck('id');

        $this->assertSame([$newer->id, $older->id], $found->all());
        $this->assertFalse($found->contains($neverSent->id));
    }

    #[Test]
    public function it_keeps_what_was_sent_after_its_rule_is_taken_away(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $sent = $this->deadline($rule, '-1 day', [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now()->subDay(),
        ]);

        $rule->delete();

        $this->assertTrue(
            ReminderOccurrence::query()->sentInVault(vault: $vault)->pluck('id')->contains($sent->id),
        );
    }

    #[Test]
    public function it_leaves_out_what_another_vault_sent(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $ofMine = $this->deadline($this->ruleIn(user: $user, vault: $here), '-1 day', [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now()->subDay(),
        ]);
        $ofOther = $this->deadline($this->ruleIn(user: $user, vault: $elsewhere), '-1 day', [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now()->subDay(),
        ]);

        $found = ReminderOccurrence::query()->sentInVault(vault: $here)->pluck('id');

        $this->assertTrue($found->contains($ofMine->id));
        $this->assertFalse($found->contains($ofOther->id));
    }

    private function ruleIn(User $user, Vault $vault): Reminder
    {
        return $this->reminderAbout(
            user: $user,
            contact: Contact::factory()->create(['vault_id' => $vault->id]),
        );
    }

    /** @param  array<string, mixed>  $attributes */
    private function deadline(Reminder $rule, string $when, array $attributes = []): ReminderOccurrence
    {
        $instant = CarbonImmutable::parse($when);

        return ReminderOccurrence::factory()->for($rule)->create(array_merge([
            'source_local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':on::',
            'scheduled_for' => $instant,
            'original_scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
        ], $attributes));
    }
}
