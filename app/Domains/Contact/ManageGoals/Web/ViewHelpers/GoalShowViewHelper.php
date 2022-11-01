<?php

namespace App\Domains\Contact\ManageGoals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\GoalHelper;
use App\Models\Contact;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GoalShowViewHelper
{
    public static function data(Contact $contact, User $user, Goal $goal): array
    {
        $date = Carbon::now();
        $startDate = Carbon::now()->subYear()->startOfMonth();
        $numberOfWeeks = $date->diffInWeeks($startDate) + 1;

        $streaks = DB::table('streaks')->where('goal_id', $goal->id)
            ->whereDate('happened_at', '<=', $date)
            ->whereDate('happened_at', '>=', $startDate)
            ->get();

        $weeksCollection = collect();
        $currentDate = $startDate->copy();
        for ($week = 1; $week <= $numberOfWeeks; $week++) {
            $streaksCollection = collect();
            for ($day = 1; $day <= 7; $day++) {
                $streakForTheDay = $streaks->where('happened_at', $currentDate)->first();
                $streaksCollection->push([
                    'id' => $day,
                    'date' => DateHelper::format($currentDate->copy(), $user),
                    'streak' => $streakForTheDay ? true : false,
                    'not_yet_happened' => $currentDate->isFuture(),
                ]);

                $currentDate->addDay();
            }

            $weeksCollection->push([
                'id' => $week,
                'streaks' => $streaksCollection,
            ]);
        }

        return [
            'name' => $goal->name,
            'active' => $goal->active,
            'streaks_statistics' => GoalHelper::getStreakData($goal),
            'weeks' => $weeksCollection,
            'count' => $streaks->count(),
            'contact' => [
                'name' => $contact->name,
            ],
            'url' => [
                'update' => route('contact.goal.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
                'destroy' => route('contact.goal.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
                'contact' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
