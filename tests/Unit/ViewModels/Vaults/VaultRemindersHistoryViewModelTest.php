<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderRow;
use App\ViewModels\Vaults\VaultRemindersHistoryViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultRemindersHistoryViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_was_answered_the_latest_answer_first(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $rule = $this->rule($vault, 'Birthday of Angela');

        $this->answered($rule, '2026-05-01', ReminderAnswerEnum::Forgotten, '2026-05-03 10:00:00');
        $this->answered($rule, '2026-06-01', ReminderAnswerEnum::Remembered, '2026-06-01 10:00:00');
        $this->answered($rule, '2026-07-01', null, null);

        $rows = new VaultRemindersHistoryViewModel(user: $user, vault: $vault)->rows();

        $this->assertSame(['You remembered', 'You forgot'], array_map(fn (ReminderRow $row): string => $row->outcome(), $rows));
    }

    #[Test]
    public function it_keeps_the_wording_and_the_contact_of_an_answer_whose_reminder_was_deleted(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $rule = $this->rule($vault, 'Birthday of Angela');
        $this->answered($rule, '2026-05-01', ReminderAnswerEnum::Remembered, '2026-05-01 10:00:00');
        $rule->delete();

        $rows = new VaultRemindersHistoryViewModel(user: $user, vault: $vault)->rows();

        $this->assertSame('Birthday of Angela', $rows[0]->text());
        $this->assertSame($rule->remindable->name, $rows[0]->contactName());
    }

    #[Test]
    public function it_leaves_out_another_vault_s_answers(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $other = Vault::factory()->create(['account_id' => $vault->account_id]);

        $this->answered($this->rule($other, 'Birthday of Kevin'), '2026-05-01', ReminderAnswerEnum::Remembered, '2026-05-01 10:00:00');

        $viewModel = new VaultRemindersHistoryViewModel(user: $user, vault: $vault);

        $this->assertSame([], $viewModel->rows());
        $this->assertSame('No answers yet', $viewModel->emptyTitle());
    }

    private function rule(Vault $vault, string $name): Reminder
    {
        $contact = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        return Reminder::factory()->manual($contact, '2026-05-01')->create(['name' => $name, 'timezone' => 'UTC']);
    }

    private function answered(Reminder $rule, string $day, ?ReminderAnswerEnum $answer, ?string $answeredAt): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day,
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
            'answer' => $answer,
            'answered_at' => $answeredAt === null ? null : CarbonImmutable::parse($answeredAt, 'UTC'),
        ]);
    }
}
