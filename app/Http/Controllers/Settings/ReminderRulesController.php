<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ReminderRule;
use App\Traits\JsonRespondController;

class ReminderRulesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the reminder rules.
     */
    public function index()
    {
        $reminderRules = auth()->user()->account->reminderRules;

        return $reminderRules->map(function ($reminderRule) {
            return $this->format($reminderRule);
        });
    }

    public function toggle(Request $request, ReminderRule $reminderRule)
    {
        $reminderRule->active = ! $reminderRule->active;
        $reminderRule->save();

        return $this->respond([
            'data' => $this->format($reminderRule),
        ]);
    }

    private function format(ReminderRule $reminderRule)
    {
        return [
            'id' => $reminderRule->id,
            'number_of_days_before' => $reminderRule->number_of_days_before,
            'active' => $reminderRule->active,
        ];
    }
}
