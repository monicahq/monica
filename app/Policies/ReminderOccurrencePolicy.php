<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;

class ReminderOccurrencePolicy
{
    public function remember(User $user, ReminderOccurrence $occurrence): Response
    {
        $refusal = $this->refusalOf($occurrence);

        if ($refusal instanceof Response) {
            return $refusal;
        }

        if ($occurrence->local_date->toDateString() > $this->today($user)) {
            return Response::deny(__('This reminder is not due yet.'));
        }

        return Response::allow();
    }

    public function forget(User $user, ReminderOccurrence $occurrence): Response
    {
        $refusal = $this->refusalOf($occurrence);

        if ($refusal instanceof Response) {
            return $refusal;
        }

        if ($occurrence->local_date->toDateString() >= $this->today($user)) {
            return Response::deny(__('You can say you forgot from the day after.'));
        }

        return Response::allow();
    }

    private function refusalOf(ReminderOccurrence $occurrence): ?Response
    {
        if (! $occurrence->exists) {
            return Response::deny(__('This reminder is not due yet.'));
        }

        if ($occurrence->answer !== null) {
            return Response::deny(__('This reminder has already been answered.'));
        }

        if ($occurrence->cancelled_at !== null) {
            return Response::deny(__('This reminder was cancelled and cannot be answered.'));
        }

        return null;
    }

    private function today(User $user): string
    {
        return CarbonImmutable::now($user->timezone)->toDateString();
    }
}
