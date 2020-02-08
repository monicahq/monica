<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Load the reminderOutboxes for the upcoming three months
        $reminderOutboxes = [
            0 => auth()->user()->account->getRemindersForMonth(0),
            1 => auth()->user()->account->getRemindersForMonth(1),
            2 => auth()->user()->account->getRemindersForMonth(2),
        ];
        return Inertia::render('Dashboard/Index', []);
    }
}
