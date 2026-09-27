<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

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
    public function it_is_active_until_it_is_answered_or_cancelled(): void
    {
        $waiting = ReminderOccurrence::factory()->create();
        $remembered = ReminderOccurrence::factory()->remembered()->create();
        $cancelled = ReminderOccurrence::factory()->cancelled()->create();

        $this->assertTrue($waiting->isActive());
        $this->assertFalse($remembered->isActive());
        $this->assertFalse($cancelled->isActive());

        $found = ReminderOccurrence::query()->active()->pluck('id');

        $this->assertSame([$waiting->id], $found->all());
    }

    #[Test]
    public function it_finds_the_deadlines_somebody_answered(): void
    {
        $waiting = ReminderOccurrence::factory()->create();
        $remembered = ReminderOccurrence::factory()->remembered()->create();
        $forgotten = ReminderOccurrence::factory()->forgotten()->create();

        $found = ReminderOccurrence::query()->answered()->pluck('id');

        $this->assertTrue($found->contains($remembered->id));
        $this->assertTrue($found->contains($forgotten->id));
        $this->assertFalse($found->contains($waiting->id));
    }

    #[Test]
    public function it_finds_the_active_deadlines_whose_instant_has_arrived_and_nobody_told_about(): void
    {
        $due = ReminderOccurrence::factory()->past()->create();
        $ahead = ReminderOccurrence::factory()->create();
        $told = ReminderOccurrence::factory()->past()->notified()->create();
        $answered = ReminderOccurrence::factory()->past()->remembered()->create();
        $cancelled = ReminderOccurrence::factory()->past()->cancelled()->create();

        $found = ReminderOccurrence::query()->dueForNotification()->pluck('id');

        $this->assertSame([$due->id], $found->all());
        $this->assertFalse($found->contains($ahead->id));
        $this->assertFalse($found->contains($told->id));
        $this->assertFalse($found->contains($answered->id));
        $this->assertFalse($found->contains($cancelled->id));
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
            'local_date' => $occurrence->local_date->toDateString(),
            'generation_key' => $occurrence->generation_key,
            'scheduled_for' => $occurrence->scheduled_for,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $written);
        $this->assertSame(1, ReminderOccurrence::query()->where('reminder_id', $occurrence->reminder_id)->count());
    }

    #[Test]
    public function it_finds_what_one_vault_is_still_waiting_on_in_date_order_up_to_the_day_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $late = $this->deadline($rule, '-3 days');
        $today = $this->deadline($rule, 'now');
        $ahead = $this->deadline($rule, '+2 days');

        $found = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $vault, untilDay: CarbonImmutable::now())
            ->pluck('id')
            ->all();

        $this->assertSame([$late->id, $today->id], $found);
        $this->assertNotContains($ahead->id, $found);
    }

    #[Test]
    public function it_leaves_out_of_what_is_waiting_a_deadline_answered_cancelled_or_whose_rule_went_quiet(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $waiting = $this->deadline($rule, '-1 day');
        $answered = $this->deadline($rule, '-2 days', ['answer' => 'forgotten', 'answered_at' => now()]);
        $cancelled = $this->deadline($rule, '-3 days', ['cancelled_at' => now(), 'cancelled_reason' => 'record_archived']);

        $quiet = $this->ruleIn(user: $user, vault: $vault);
        $quiet->is_active = false;
        $quiet->save();
        $ofQuiet = $this->deadline($quiet, '-1 day');

        $found = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $vault, untilDay: CarbonImmutable::now())
            ->pluck('id');

        $this->assertSame([$waiting->id], $found->all());
        $this->assertFalse($found->contains($answered->id));
        $this->assertFalse($found->contains($cancelled->id));
        $this->assertFalse($found->contains($ofQuiet->id));
    }

    #[Test]
    public function it_leaves_out_what_another_vault_or_another_account_is_waiting_on(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $stranger = User::factory()->create();
        $theirVault = Vault::factory()->create(['account_id' => $stranger->account_id]);

        $ofMine = $this->deadline($this->ruleIn(user: $user, vault: $here), '-1 day');
        $this->deadline($this->ruleIn(user: $user, vault: $elsewhere), '-1 day');
        $this->deadline($this->ruleIn(user: $stranger, vault: $theirVault), '-1 day');

        $found = ReminderOccurrence::query()
            ->awaitingAnswerInVault(vault: $here, untilDay: CarbonImmutable::now())
            ->pluck('id');

        $this->assertSame([$ofMine->id], $found->all());
    }

    #[Test]
    public function it_finds_what_one_vault_answered_the_latest_answer_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $older = $this->deadline($rule, '-9 days', ['answer' => 'remembered', 'answered_at' => now()->subDays(8)]);
        $newer = $this->deadline($rule, '-2 days', ['answer' => 'forgotten', 'answered_at' => now()->subDay()]);
        $this->deadline($rule, '-1 day');
        $this->deadline($this->ruleIn(user: $user, vault: $elsewhere), '-1 day', ['answer' => 'remembered', 'answered_at' => now()]);

        $found = ReminderOccurrence::query()->answeredInVault(vault: $vault)->pluck('id');

        $this->assertSame([$newer->id, $older->id], $found->all());
    }

    #[Test]
    public function it_finds_what_one_vault_has_already_sent_most_recent_first_even_once_its_rule_is_gone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn(user: $user, vault: $vault);

        $older = $this->deadline($rule, '-9 days', ['notified_at' => now()->subDays(9)]);
        $newer = $this->deadline($rule, '-1 day', ['notified_at' => now()->subDay()]);
        $this->deadline($rule, '+7 days');

        $rule->delete();

        $found = ReminderOccurrence::query()->sentInVault(vault: $vault)->pluck('id');

        $this->assertSame([$newer->id, $older->id], $found->all());
    }

    #[Test]
    public function it_is_still_warranted_only_while_active_its_rule_live_and_its_contact_not_archived(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rule = $this->ruleIn(user: $user, vault: $vault);
        $waiting = $this->deadline($rule, 'now');
        $answered = $this->deadline($rule, '-1 day', ['answer' => 'remembered', 'answered_at' => now()]);

        $archivedContact = Contact::factory()->create(['vault_id' => $vault->id, 'is_archived' => true]);
        $ofArchived = $this->deadline($this->reminderAbout(user: $user, contact: $archivedContact), 'now');

        $removed = $this->ruleIn(user: $user, vault: $vault);
        $ofRemoved = $this->deadline($removed, 'now');
        $removed->delete();

        $this->assertTrue($waiting->refresh()->isStillWarranted());
        $this->assertFalse($answered->refresh()->isStillWarranted());
        $this->assertFalse($ofArchived->refresh()->isStillWarranted());
        $this->assertFalse($ofRemoved->refresh()->isStillWarranted());
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
            'local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':'.Str::random(6),
            'scheduled_for' => $instant,
        ], $attributes));
    }
}
