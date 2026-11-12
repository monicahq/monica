<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Logs;

use App\Models\EmailSent;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\EmailsSentViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailsSentViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_emails_sent_newest_first(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'subject' => 'Confirm your email address',
            'sent_at' => now()->subDays(3),
        ]);
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'subject' => 'A sign-in from a new place',
            'sent_at' => now()->subDay(),
        ]);

        $viewModel = new EmailsSentViewModel(user: $user);

        $this->assertCount(2, $viewModel->emailsSent());
        $this->assertEquals('A sign-in from a new place', $viewModel->emailsSent()->first()->subject);
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

        $viewModel = new EmailsSentViewModel(user: $user);

        $this->assertCount(0, $viewModel->emailsSent());
    }
}
