<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\NotificationChannelEnum;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * What the notification bar shows: how many are unread, and a page of the most
 * recent ones.
 *
 * The bar is the in application channel made visible, so it shows the rows
 * written for that channel and no other. A reminder that also went out by email
 * has a row of its own for the email, and showing both would say the same thing
 * twice.
 *
 * The page is bounded whatever the account has accumulated. A person who has
 * not looked in a year has a year of notifications behind the bar, and reading
 * all of them to draw a list of ten would slow every signed in screen. Every
 * screen asks only for the count: the page is read when the panel is opened,
 * and the next one when Load more is asked for.
 */
class NotificationBarViewModel
{
    public const int PER_PAGE = 25;

    public function __construct(
        private readonly User $user,
        private readonly int $page = 1,
    ) {}

    public function unreadCount(): int
    {
        return $this->scope()->whereNull('read_at')->count();
    }

    public function page(): int
    {
        return $this->page;
    }

    /** @return Paginator<int, array<string, mixed>> */
    public function notifications(): Paginator
    {
        return $this->paginator();
    }

    public function nextPageUrl(): ?string
    {
        return $this->paginator()->nextPageUrl();
    }

    /** @return Paginator<int, array<string, mixed>> */
    private function paginator(): Paginator
    {
        return once(fn (): Paginator => $this->scope()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE, ['*'], 'page', $this->page)
            ->withPath(route('notifications.index'))
            ->through(fn (Notification $notification): array => $this->shapeOf($notification)));
    }

    /** @return array<string, mixed> */
    private function shapeOf(Notification $notification): array
    {
        return [
            'uuid' => $notification->uuid,
            'title' => $notification->title,
            'body' => $notification->body,
            'is_read' => $notification->read_at !== null,
            'when' => $notification->created_at->setTimezone($this->user->timezone)->diffForHumans(),
            'url' => route('notifications.read.update', $notification->uuid),
        ];
    }

    /** @return Builder<Notification> */
    private function scope(): Builder
    {
        return Notification::query()
            ->where('account_id', $this->user->account_id)
            ->where('user_id', $this->user->id)
            ->where('channel', NotificationChannelEnum::InApp->value)
            ->whereNotNull('sent_at')
            ->whereNull('failed_at');
    }
}
