<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupportRequestPolicy
{
    public function solve(User $user, SupportRequest $supportRequest): Response
    {
        if ($supportRequest->status === SupportRequestStatusEnum::Solved) {
            return Response::deny(__('This request is already solved.'));
        }

        return Response::allow();
    }
}
