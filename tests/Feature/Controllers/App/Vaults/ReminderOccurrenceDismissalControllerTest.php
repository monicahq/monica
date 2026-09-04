<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceDismissalControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_closes_one_deadline(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadlineFor($this->rule($vault), '+2 days', 'a');

        $response = $this->actingAs($user)->put($this->url($vault, $occurrence));

        $response->assertRedirect(route('reminders.index', $vault));
        $response->assertSessionHas('status');

        $this->assertEquals(ReminderOccurrenceStatusEnum::Dismissed, $occurrence->refresh()->status);
        $this->assertNotNull($occurrence->dismissed_at);
    }

    /**
     * Being done with this year's birthday does not switch the rule off, so
     * next year still arrives.
     */
    #[Test]
    public function it_leaves_the_rule_producing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->rule($vault);
        $occurrence = $this->deadlineFor($rule, '+2 days', 'a');

        $this->actingAs($user)->put($this->url($vault, $occurrence));

        $this->assertTrue($rule->refresh()->is_active);
    }

    #[Test]
    public function it_leaves_the_sibling_deadline_of_the_same_cycle_alone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->rule($vault);

        $dismissing = $this->deadlineFor($rule, '+2 days', 'a');
        $sibling = $this->deadlineFor($rule, '+9 days', 'b');

        $this->actingAs($user)->put($this->url($vault, $dismissing));

        $this->assertEquals(ReminderOccurrenceStatusEnum::Pending, $sibling->refresh()->status);

        $this->actingAs($user)
            ->get(route('reminders.index', $vault))
            ->assertSee('Stanley Hudson');
    }

    #[Test]
    public function the_row_leaves_the_list_once_it_is_closed(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadlineFor($this->rule($vault), '+2 days', 'a');

        $this->actingAs($user)->put($this->url($vault, $occurrence));

        $this->actingAs($user)
            ->get(route('reminders.index', $vault))
            ->assertSee('Nothing due just yet');
    }

    #[Test]
    public function it_refuses_a_deadline_somebody_has_already_dealt_with(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadlineFor($this->rule($vault), '+2 days', 'a');
        $occurrence->status = ReminderOccurrenceStatusEnum::Dismissed;
        $occurrence->save();

        $this->actingAs($user)
            ->put($this->url($vault, $occurrence))
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_refuses_a_deadline_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $occurrence = $this->deadlineFor($this->rule($vault), '+2 days', 'a');

        $this->actingAs($user)->put($this->url($vault, $occurrence))->assertStatus(404);
    }

    #[Test]
    public function it_refuses_a_deadline_reached_through_the_wrong_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadlineFor($this->rule($vault), '+2 days', 'a');

        $this->actingAs($user)->put($this->url($other, $occurrence))->assertStatus(404);
    }

    private function url(Vault $vault, ReminderOccurrence $occurrence): string
    {
        return route('reminders.occurrences.dismissal.update', [
            'vault' => $vault,
            'reminderOccurrence' => $occurrence,
        ]);
    }

    private function rule(Vault $vault): Reminder
    {
        return Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'timezone' => 'Europe/Paris',
            'name' => 'Stanley Hudson',
        ]);
    }

    private function deadlineFor(Reminder $rule, string $when, string $key): ReminderOccurrence
    {
        $instant = CarbonImmutable::parse($when);

        return ReminderOccurrence::factory()->for($rule)->create([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'source_local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':'.$key,
            'scheduled_for' => $instant,
            'original_scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending,
        ]);
    }
}
