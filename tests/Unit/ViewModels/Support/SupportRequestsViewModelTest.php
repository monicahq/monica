<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Support;

use App\Models\SupportRequest;
use App\Models\User;
use App\ViewModels\Support\SupportRequestsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_separates_open_requests_from_solved_ones_latest_activity_first(): void
    {
        $user = User::factory()->create();
        SupportRequest::factory()->create(['account_id' => $user->account_id, 'subject' => 'Invoice name', 'last_activity_at' => now()->subDays(2)]);
        SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id, 'subject' => 'Late reminders', 'last_activity_at' => now()]);
        SupportRequest::factory()->solved()->create(['account_id' => $user->account_id, 'subject' => 'Share one contact']);
        SupportRequest::factory()->create(['subject' => 'Stamford branch closing']);

        $open = new SupportRequestsViewModel(user: $user);
        $solved = new SupportRequestsViewModel(user: $user, showSolved: true);

        $this->assertEquals(['Late reminders', 'Invoice name'], array_column($open->rows(), 'subject'));
        $this->assertEquals(['Share one contact'], array_column($solved->rows(), 'subject'));
        $this->assertEquals(2, $open->openCount());
        $this->assertEquals(1, $open->solvedCount());
    }

    #[Test]
    public function it_words_the_status_for_the_user(): void
    {
        $user = User::factory()->create();
        SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        $this->assertEquals('Reply from support', new SupportRequestsViewModel(user: $user)->rows()[0]['statusLabel']);
    }
}
