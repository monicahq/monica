<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use App\ViewModels\NotificationBarViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationBarViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_the_application_has_said_newest_first(): void
    {
        $user = User::factory()->create();
        $this->notification(user: $user, title: 'Birthday, Michael Scott', when: now()->subDays(2));
        $this->notification(user: $user, title: 'Anniversary, Jim Halpert', when: now()->subHour());

        $viewModel = new NotificationBarViewModel(user: $user);

        $this->assertSame(
            ['Anniversary, Jim Halpert', 'Birthday, Michael Scott'],
            $viewModel->notifications()->pluck('title')->all(),
        );
    }

    #[Test]
    public function it_counts_what_has_not_been_read(): void
    {
        $user = User::factory()->create();
        $this->notification(user: $user, title: 'Birthday, Michael Scott', when: now());
        $this->notification(user: $user, title: 'Anniversary, Jim Halpert', when: now())->update(['read_at' => now()]);

        $viewModel = new NotificationBarViewModel(user: $user);

        $this->assertSame(1, $viewModel->unreadCount());
    }

    #[Test]
    public function it_leaves_out_what_went_out_on_another_channel(): void
    {
        $user = User::factory()->create();
        $this->notification(user: $user, title: 'Sent by email only', when: now(), channel: NotificationChannelEnum::Email);

        $viewModel = new NotificationBarViewModel(user: $user);

        $this->assertTrue($viewModel->notifications()->isEmpty());
        $this->assertSame(0, $viewModel->unreadCount());
    }

    #[Test]
    public function it_leaves_out_what_belongs_to_another_account(): void
    {
        $user = User::factory()->create();
        $them = User::factory()->create(['account_id' => Account::factory()->create()->id]);
        $this->notification(user: $them, title: 'Their business', when: now());

        $viewModel = new NotificationBarViewModel(user: $user);

        $this->assertTrue($viewModel->notifications()->isEmpty());
        $this->assertSame(0, $viewModel->unreadCount());
    }

    #[Test]
    public function it_reads_one_page_whatever_has_accumulated(): void
    {
        config(['monica.api_default_items_per_page' => 2]);

        $user = User::factory()->create();
        $this->notification(user: $user, title: 'Birthday, Michael Scott', when: now());
        $this->notification(user: $user, title: 'Anniversary, Jim Halpert', when: now());
        $this->notification(user: $user, title: 'Birthday, Dwight Schrute', when: now());

        $viewModel = new NotificationBarViewModel(user: $user);

        $this->assertCount(2, $viewModel->notifications());
    }

    private function notification(User $user, string $title, mixed $when, NotificationChannelEnum $channel = NotificationChannelEnum::InApp): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'title' => $title,
            'sent_at' => $when,
            'created_at' => $when,
        ]);
    }
}
