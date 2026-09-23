<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\Notifications\ReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceTestNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_reminder_to_whoever_pressed_the_button(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault);

        $response = $this->actingAs($user)->post($this->url($vault, $occurrence));

        $response->assertRedirect(route('reminders.index', $vault));
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ReminderDue::class);
    }

    #[Test]
    public function it_produces_no_row_on_the_list_of_what_was_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault);

        $this->actingAs($user)->post($this->url($vault, $occurrence));

        $this->actingAs($user)
            ->get(route('reminders.sent', $vault))
            ->assertSee('Nothing has been sent yet');
    }

    #[Test]
    public function pressing_it_twice_still_produces_no_row(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault);

        $this->actingAs($user)->post($this->url($vault, $occurrence));
        $this->actingAs($user)->post($this->url($vault, $occurrence));

        $this->assertEquals(0, ReminderOccurrence::query()->sentInVault($vault)->count());
    }

    #[Test]
    public function it_refuses_a_deadline_reached_through_the_wrong_vault(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault);

        $this->actingAs($user)->post($this->url($other, $occurrence))->assertStatus(404);

        Notification::assertNothingSent();
    }

    private function url(Vault $vault, ReminderOccurrence $occurrence): string
    {
        return route('reminders.occurrences.testNotification.create', [
            'vault' => $vault,
            'reminderOccurrence' => $occurrence,
        ]);
    }

    private function deadline(Vault $vault): ReminderOccurrence
    {
        NotificationPreference::factory()->create(['account_id' => $vault->account_id]);

        $rule = Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'name' => 'Stanley Hudson',
            'timezone' => 'Europe/Paris',
        ]);

        $instant = CarbonImmutable::now()->addDays(3);

        return ReminderOccurrence::factory()->for($rule)->create([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'source_local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':a',
            'scheduled_for' => $instant,
            'original_scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending,
            'notified_at' => null,
        ]);
    }
}
