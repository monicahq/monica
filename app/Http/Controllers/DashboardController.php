<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Debt;
use Illuminate\Http\Request;
use function Safe\json_encode;
use App\Helpers\InstanceHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Debt\Debt as DebtResource;

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
