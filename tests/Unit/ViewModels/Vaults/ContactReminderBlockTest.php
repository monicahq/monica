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

        Reminder::factory()->create(['cadence' => ReminderCadenceEnum::Monthly, 'frequency_interval' => 3]);

    }

    #[Test]
    public function it_says_when_a_saved_rule_next_falls_and_nothing_for_a_new_one(): void
    {
        $this->travelTo('2026-09-27 12:00:00');

        $reminder = Reminder::factory()->create(['time_of_day' => '09:00:00', 'timezone' => 'UTC']);

        $this->assertNull($this->block()->nextLine());
        $this->assertStringContainsString('09:00', (string) $this->block($reminder)->nextLine());
    }

    #[Test]
    public function it_says_the_next_date_is_next_year_once_today_has_been_told(): void
    {
        $this->travelTo('2026-09-28 12:00:00');
        $contact = Contact::factory()->create();

        $reminder = Reminder::factory()->manual($contact, '2026-09-28')->create(['time_of_day' => '09:00:00', 'timezone' => 'UTC']);

        $this->assertStringContainsString('2027', (string) $this->block($reminder)->nextLine());
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
