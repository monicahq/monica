<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\SupportQueue;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use App\ViewModels\Admin\SupportQueueViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportQueueViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_oldest_request_in_view_and_counts_every_view(): void
    {
        SupportRequest::factory()->toHandle()->create(['subject' => 'Reminders not received', 'created_at' => now()->subHours(3)]);
        SupportRequest::factory()->toHandle()->create(['subject' => 'vCard import stops at 50', 'created_at' => now()->subDays(8)]);

        $viewModel = new SupportQueueViewModel(administrator: User::factory()->create(), queue: new SupportQueue);

        $this->assertEquals('vCard import stops at 50', $viewModel->currentSummary()['subject']);
        $this->assertEquals([true, false], array_column($viewModel->rows(), 'current'));
        $this->assertEquals(['To handle', 'Waiting on the user', 'Solved', 'All'], array_column($viewModel->views(), 'label'));
    }

    #[Test]
    public function it_says_why_the_list_is_empty(): void
    {
        $administrator = User::factory()->create();

        $this->assertEquals('Nothing to handle', new SupportQueueViewModel(administrator: $administrator, queue: new SupportQueue)->emptyTitle());
        $this->assertEquals('Nothing matches these filters', new SupportQueueViewModel(administrator: $administrator, queue: new SupportQueue(search: 'Stamford'))->emptyTitle());
    }

    #[Test]
    public function it_shows_the_administrators_the_notes_and_the_history_of_the_open_request(): void
    {
        $supportRequest = SupportRequest::factory()->create();
        SupportMessage::factory()->create(['support_request_id' => $supportRequest->id]);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id, 'body' => 'Check the domain reputation first.']);
        SupportStatusChange::factory()->create(['support_request_id' => $supportRequest->id, 'user_name' => 'Camille Laurent']);
        SupportStatusChange::factory()->create(['support_request_id' => $supportRequest->id, 'from_status' => 'to_handle', 'to_status' => 'waiting_on_user', 'user_name' => 'Jim Halpert']);

        $viewModel = new SupportQueueViewModel(administrator: User::factory()->create(), queue: new SupportQueue(view: 'all'), selected: $supportRequest);

        $this->assertEquals(['message', 'note'], array_column($viewModel->thread(), 'kind'));
        $this->assertEquals(['Moved to Waiting on the user', 'Opened'], array_column($viewModel->history(), 'line'));
    }
}
