<?php

namespace App\Http\Controllers\Web\Dashboard;

use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @return Response
     */
    public function index(): Response
    {
        // Grab the 15 next reminders
        $startOfMonth = now(DateHelper::getTimezone())->startOfMonth();
        $endOfMonth = now(DateHelper::getTimezone())->addMonthsNoOverflow(2)->endOfMonth();

        $reminders = Auth::user()->account->reminderOutboxes()
            ->with(['reminder', 'reminder.contact'])
            ->whereBetween('planned_date', [$startOfMonth, $endOfMonth])
            ->where('nature', 'reminder')
            ->orderBy('planned_date', 'asc')
            ->take(15)
            ->get();

        $remindersCollection = collect();
        foreach ($reminders as $reminderOutbox) {
            $remindersCollection->push([
                'id' => $reminderOutbox->id,
                'day' => $reminderOutbox->planned_date->day,
                'month' => $reminderOutbox->planned_date->format('M'),
                'title' => $reminderOutbox->reminder->title,
                'contact' => [
                    'id' => $reminderOutbox->reminder->contact->id,
                    'name' => $reminderOutbox->reminder->contact->name,
                    'avatar' => $reminderOutbox->reminder->contact->getAvatarURL(),
                    'url' => route('people.show', ['contact' => $reminderOutbox->reminder->contact]),
                ],
            ]);
        }

        return Inertia::render('Dashboard/Index', [
            'reminders' => $remindersCollection,
        ]);
    }
}
