<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ReminderCadenceEnum;
use App\Helpers\ContactReminderDates;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderDatesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_each_kind_of_date_about_one_contact(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $chandler = Contact::factory()->create(['account_id' => $user->account_id]);
        $flat = $this->rule($chandler, 'Call about the flat on Bedford Street', '2026-10-26', ReminderCadenceEnum::Monthly);

        $overdue = $this->date($flat, '2026-09-20');
        $today = $this->date($flat, '2026-09-26');
        $remembered = $this->date($flat, '2026-08-26', ['answer' => 'remembered', 'answered_at' => now()->subMonth()]);

        $this->assertSame([$overdue->id], $this->ids($user, $chandler, 'overdue'));
        $this->assertSame([$today->id], $this->ids($user, $chandler, 'today'));
        $this->assertSame([$remembered->id], $this->ids($user, $chandler, 'history'));
        $this->assertSame(['2026-10-26'], ContactReminderDates::page(user: $user, contact: $chandler, category: 'upcoming', perPage: 100, page: 1)->getCollection()
            ->map(fn (ReminderOccurrence $date): string => $date->local_date->toDateString())
            ->all());
        $this->assertSame([$today->id, $overdue->id, $remembered->id], $this->ids($user, $chandler, null));
    }

    #[Test]
    public function it_keeps_the_answers_of_a_reminder_deleted_since(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $chandler = Contact::factory()->create(['account_id' => $user->account_id]);
        $tulsa = $this->rule($chandler, 'Ask about Tulsa', '2026-12-01', ReminderCadenceEnum::Once);
        $forgotten = $this->date($tulsa, '2026-03-02', ['answer' => 'forgotten', 'answered_at' => now()->subMonths(6)]);

        $tulsa->delete();

        $history = ContactReminderDates::page(user: $user, contact: $chandler, category: 'history', perPage: 100, page: 1)->getCollection();

        $this->assertSame([$forgotten->id], $history->pluck('id')->all());
        $this->assertSame('Ask about Tulsa', $history->first()->reminder->name);
    }

    #[Test]
    public function it_leaves_out_the_dates_of_another_contact(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $chandler = Contact::factory()->create(['account_id' => $user->account_id]);
        $joey = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $chandler->vault_id]);

        $this->date($this->rule($joey, 'Audition for Days of Our Lives', '2026-10-01', ReminderCadenceEnum::Once), '2026-09-20');

        $this->assertSame([], $this->ids($user, $chandler, null));
        $this->assertSame([], $this->ids($user, $chandler, 'overdue'));
    }

    /** @return list<int|null> */
    private function ids(User $user, Contact $contact, ?string $category): array
    {
        return ContactReminderDates::page(user: $user, contact: $contact, category: $category, perPage: 100, page: 1)->getCollection()->pluck('id')->all();
    }

    private function rule(Contact $contact, string $name, string $startsOn, ReminderCadenceEnum $cadence): Reminder
    {
        return Reminder::factory()->manual($contact, $startsOn)->create([
            'name' => $name,
            'cadence' => $cadence,
            'timezone' => 'UTC',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function date(Reminder $rule, string $day, array $attributes = []): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'local_date' => $day,
            'generation_key' => "1:{$day}",
            'scheduled_for' => "{$day} 09:00:00",
            ...$attributes,
        ]);
    }
}
