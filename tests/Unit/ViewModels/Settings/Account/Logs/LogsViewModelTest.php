<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Logs;

use App\Enums\UserActionEnum;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\LogsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_logs_of_the_user_newest_first(): void
    {
        $user = User::factory()->create();

        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserLogin->value,
            'created_at' => now()->subDays(3),
        ]);
        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserPasswordUpdate->value,
            'created_at' => now()->subDay(),
        ]);

        $viewModel = new LogsViewModel(user: $user);
        $logs = $viewModel->logs();

        $this->assertCount(2, $logs);
        $this->assertEquals(UserActionEnum::UserPasswordUpdate->value, $logs[0]->action);
        $this->assertEquals(UserActionEnum::UserLogin->value, $logs[1]->action);
    }

    #[Test]
    public function it_leaves_out_the_logs_of_somebody_else(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        Log::factory()->create([
            'account_id' => $stranger->account_id,
            'user_id' => $stranger->id,
        ]);

        $viewModel = new LogsViewModel(user: $user);

        $this->assertCount(0, $viewModel->logs());
    }

    #[Test]
    public function it_gives_the_entries_the_application_wrote_on_its_own(): void
    {
        $user = User::factory()->create();

        Log::factory()->bySystem()->create([
            'account_id' => $user->account_id,
            'action' => UserActionEnum::UserLogin->value,
        ]);

        $viewModel = new LogsViewModel(user: $user);

        $this->assertCount(1, $viewModel->logs());
        $this->assertEquals('The system', $viewModel->logs()->first()->author);
    }

    #[Test]
    public function it_gives_the_last_five_emails_sent_newest_first(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(6)->sequence(fn ($sequence): array => [
            'subject' => 'Email '.$sequence->index,
            'sent_at' => now()->subDays(6 - $sequence->index),
        ])->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user);

        $this->assertCount(5, $viewModel->emailsSent());
        $this->assertEquals('Email 5', $viewModel->emailsSent()->first()->subject);
        $this->assertTrue($viewModel->hasMoreEmailsSent());
    }

    #[Test]
    public function it_says_there_is_nothing_more_to_read_when_there_are_five_emails_or_fewer(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(5)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user);

        $this->assertFalse($viewModel->hasMoreEmailsSent());
    }

    #[Test]
    public function it_hides_the_emails_sent_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $stranger->account_id,
            'user_id' => $stranger->id,
        ]);

        $viewModel = new LogsViewModel(user: $user);

        $this->assertCount(0, $viewModel->emailsSent());
        $this->assertFalse($viewModel->hasMoreEmailsSent());
    }
}
