<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\MarkNotificationsAsRead;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function update(Request $request, Notification $notification): RedirectResponse
    {
        new MarkNotificationsAsRead(
            user: $request->user(),
            notification: $notification,
        )->execute();

        return redirect($this->whereItLeadsTo($notification));
    }

    public function updateAll(Request $request): RedirectResponse
    {
        new MarkNotificationsAsRead(user: $request->user())->execute();

        return back()->with('status', __('Everything is marked as read.'));
    }

    /**
     * A notification takes you to the thing it is about. A summary is about
     * several things at once and a pruned deadline is about nothing that is
     * still there, so both of those land back where you were.
     */
    private function whereItLeadsTo(Notification $notification): string
    {
        $occurrence = $notification->notifiable;

        if (! $occurrence instanceof ReminderOccurrence) {
            return url()->previous();
        }

        return route('reminders.show', [$occurrence->vault_id, $occurrence->reminder_id]);
    }
}
