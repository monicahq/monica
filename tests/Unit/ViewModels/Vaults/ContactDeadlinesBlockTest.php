<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactDeadlinesBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDeadlinesBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $chandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->chandler = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => 'Chandler',
            'last_name' => 'Bing',
        ]);
    }

    #[Test]
    public function it_lists_every_deadline_waiting_earliest_first_with_its_category(): void
    {
        $this->rule('Ask how the new job is going', '2026-10-14');
        $this->deadline($this->rule('Call about the flat on Bedford Street', '2026-09-26'), '2026-09-26');
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');

        $rows = $this->block()->rows();

        $this->assertSame(['overdue', 'today', 'upcoming'], array_column($rows, 'category'));
        $this->assertSame(
            ['Send him the photos from the wedding', 'Call about the flat on Bedford Street', 'Ask how the new job is going'],
            array_map(fn (array $row): string => $row['row']->text(), $rows),
        );
    }

    #[Test]
    public function it_counts_each_filter_and_leaves_the_history_to_the_reminders_screen(): void
    {
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');
        $this->rule('Ask how the new job is going', '2026-10-14');
        ReminderOccurrence::factory()->for($this->rule('Return the foosball table', '2026-08-12'))->remembered()->create([
            'scheduled_for' => CarbonImmutable::parse('2026-08-12 09:00:00', 'UTC'),
            'local_date' => '2026-08-12',
        ]);

        $filters = $this->block()->filters();

        $this->assertSame(['all', 'overdue', 'today', 'upcoming'], array_column($filters, 'key'));
        $this->assertSame(['All', 'Overdue', 'Today', 'Upcoming'], array_column($filters, 'label'));
        $this->assertSame([2, 1, 0, 1], array_column($filters, 'count'));
    }

    #[Test]
    public function it_never_reads_the_answers_for_the_contact_page(): void
    {
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');
        ReminderOccurrence::factory()->for($this->rule('Return the foosball table', '2026-08-12'))->remembered()->create([
            'scheduled_for' => CarbonImmutable::parse('2026-08-12 09:00:00', 'UTC'),
            'local_date' => '2026-08-12',
        ]);

        DB::enableQueryLog();
        $block = $this->block();
        $block->rows();
        $block->filters();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $this->assertNotEmpty($queries);
        $this->assertSame([], preg_grep('/reminder_occurrences.*answer.{0,2} is not null/i', $queries));
    }

    #[Test]
    public function it_leaves_out_a_cancelled_deadline_and_a_deleted_reminder(): void
    {
        ReminderOccurrence::factory()->for($this->rule('Ask about Tulsa', '2026-09-20'))->cancelled()->create([
            'scheduled_for' => CarbonImmutable::parse('2026-09-20 09:00:00', 'UTC'),
            'local_date' => '2026-09-20',
        ]);
        $gone = $this->rule('Return the foosball table', '2026-09-24');
        $this->deadline($gone, '2026-09-24');
        $gone->delete();

        $this->assertSame([], $this->block()->rows());
        $this->assertTrue($this->block()->holdsNothing());
    }

    #[Test]
    public function a_contact_with_nothing_to_remember_is_named_in_the_blank_panel(): void
    {
        $block = $this->block();

        $this->assertTrue($block->holdsNothing());
        $this->assertSame('Nothing to remember about Chandler yet', $block->blankTitle());
        $this->assertSame('Ask to be told about a date on their page, such as a birthday.', $block->blankLine());
    }

    #[Test]
    public function it_leads_to_all_of_the_contact_s_reminders(): void
    {
        $block = $this->block();

        $this->assertSame('All of Chandler’s reminders', $block->allLabel());
        $this->assertSame(
            route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->chandler]),
            $block->remindersUrl(),
        );
    }

    #[Test]
    public function it_offers_to_add_a_reminder_unless_the_contact_is_archived(): void
    {
        $this->assertTrue($this->block()->canAdd());

        $this->chandler->update(['is_archived' => true]);

        $this->assertFalse($this->block()->canAdd());
    }

    #[Test]
    public function it_reopens_the_form_only_when_the_last_submission_came_back(): void
    {
        $this->assertFalse($this->block()->isAdding());

        request()->setLaravelSession(session()->driver());
        session()->put('_old_input', ['cadence' => 'once']);

        $this->assertTrue($this->block()->isAdding());
    }

    #[Test]
    public function it_names_the_contact_and_the_four_repeats_of_the_form(): void
    {
        $block = $this->block();

        $this->assertSame('About Chandler Bing', $block->aboutLine());
        $this->assertSame(
            ['once' => 'Once', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'],
            $block->repeats(),
        );
    }

    #[Test]
    public function it_hands_the_browser_every_wording_of_each_interval_with_room_for_the_number(): void
    {
        $wordings = $this->block()->intervalWordings();

        $this->assertSame(['weekly', 'monthly', 'yearly'], array_keys($wordings));
        $this->assertEquals(['one' => 'Every :n week', 'other' => 'Every :n weeks'], $wordings['weekly']);
    }

    private function block(): ContactDeadlinesBlock
    {
        return new ContactDeadlinesBlock(user: $this->user, vault: $this->vault, contact: $this->chandler);
    }

    private function rule(string $name, string $startsOn): Reminder
    {
        return Reminder::factory()->manual($this->chandler, $startsOn)->once()->create([
            'name' => $name,
            'timezone' => 'UTC',
        ]);
    }

    private function deadline(Reminder $rule, string $day): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
        ]);
    }
}
