<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Reminder;
use App\Models\User;
use App\ViewModels\Vaults\ContactReminderBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_new_reminder_on_every_year(): void
    {
        $this->assertSame('yearly:1', $this->block()->repeat());
    }

    #[Test]
    public function it_names_the_locale_the_way_the_browser_names_one(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('pt-BR', $this->block()->live()['locale']);
    }

    #[Test]
    public function it_reads_a_saved_rhythm_back_as_one_choice_of_the_list(): void
    {
        $once = Reminder::factory()->once()->create();
        $everyFewYears = Reminder::factory()->everyFewYears(5)->create();

        $this->assertSame('once', $this->block($once)->repeat());
        $this->assertSame('yearly:5', $this->block($everyFewYears)->repeat());
    }

    #[Test]
    public function it_offers_once_and_four_yearly_rhythms_and_keeps_an_older_one_on_offer(): void
    {
        $this->assertSame(['once', 'yearly:1', 'yearly:2', 'yearly:5', 'yearly:10'], array_keys($this->block()->repeats()));

        $monthly = Reminder::factory()->create(['cadence' => ReminderCadenceEnum::Monthly, 'frequency_interval' => 3]);

        $this->assertSame('Every 3 months', $this->block($monthly)->repeats()['monthly:3']);
    }

    #[Test]
    public function it_says_when_a_saved_rule_next_falls_and_nothing_for_a_new_one(): void
    {
        $this->travelTo('2026-09-27 12:00:00');

        $reminder = Reminder::factory()->create(['time_of_day' => '09:00:00', 'timezone' => 'UTC']);

        $this->assertNull($this->block()->nextLine());
        $this->assertStringContainsString('Next reminder on', (string) $this->block($reminder)->nextLine());
        $this->assertStringContainsString('at 09:00', (string) $this->block($reminder)->nextLine());
    }

    #[Test]
    public function it_says_a_saved_rule_is_told_on_the_day_at_its_hour(): void
    {
        $reminder = Reminder::factory()->create(['time_of_day' => '08:30:00', 'timezone' => 'America/New_York']);

        $summary = $this->block($reminder)->summary();

        $this->assertSame('You will be reminded on the day, every year, at 08:30 America/New_York.', $summary);
    }

    private function block(?Reminder $reminder = null): ContactReminderBlock
    {
        $user = User::factory()->create();

        return new ContactReminderBlock(
            user: $user,
            contact: Contact::factory()->create(['account_id' => $user->account_id]),
            field: Field::factory()->create(['account_id' => $user->account_id]),
            reminder: $reminder,
        );
    }
}
