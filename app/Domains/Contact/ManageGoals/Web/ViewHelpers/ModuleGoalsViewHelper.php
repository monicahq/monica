<?php

namespace App\Domains\Contact\ManageGoals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\GoalHelper;
use App\Models\Contact;
use App\Models\Goal;
use Carbon\Carbon;

class ModuleGoalsViewHelper
{
    public static function data(Contact $contact): array
    {
        $goals = $contact->goals()->get();
        $activeGoals = $goals->filter(function ($goal) {
            return $goal->active;
        });
        $inactiveGoals = $goals->filter(function ($goal) {
            return ! $goal->active;
        });

        $goalsCollection = $activeGoals->map(function ($goal) use ($contact) {
            return self::dto($contact, $goal);
        });

        return [
            'active_goals' => $goalsCollection,
            'inactive_goals_count' => $inactiveGoals->count(),
            'url' => [
                'store' => route('contact.goal.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'active' => $goal->active,
            'streaks_statistics' => GoalHelper::getStreakData($goal),
            'last_7_days' => self::getLast7Days($goal),
            'url' => [
                'show' => route('contact.goal.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
                'update' => route('contact.goal.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
                'streak_update' => route('contact.goal.streak.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
                'destroy' => route('contact.goal.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'goal' => $goal->id,
                ]),
            ],
        ];
    }

    private static function getLast7Days(Goal $goal): array
    {
        $streaks = $goal->streaks()
            ->whereDate('happened_at', '<=', Carbon::now())
            ->whereDate('happened_at', '>=', Carbon::now()->copy()->subDays(7))
            ->get();

        $last7DaysCollection = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);

            $streak = $streaks->first(function ($streak) use ($date) {
                return $streak->happened_at->format('Y-m-d') === $date->format('Y-m-d');
            });

            $last7DaysCollection->push([
                'id' => $i,
                'day' => DateHelper::formatShortDay($date),
                'day_number' => $date->format('d'),
                'happened_at' => $date->format('Y-m-d'),
                'active' => $streak ? true : false,
            ]);
        }

        return $last7DaysCollection->sortByDesc('id')->values()->all();
    }
}
