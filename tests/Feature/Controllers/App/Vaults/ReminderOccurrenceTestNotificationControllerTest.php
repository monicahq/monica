<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

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
    public function it_brings_the_reader_back_to_the_grouping_they_sent_it_from(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault);

        $response = $this->actingAs($user)
            ->from(route('reminders.byContact', $vault))
            ->post($this->url($vault, $occurrence));

        $response->assertRedirect(route('reminders.byContact', $vault));
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
            'generation_key' => '1:'.$instant->toDateString(),
            'scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
            'notified_at' => null,
        ]);
    }
}
