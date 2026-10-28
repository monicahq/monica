<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountImportPolicy
{
    public function create(User $user): Response
    {
        $isWaitingForADecision = AccountImport::query()
            ->where('account_id', $user->account_id)
            ->where('status', AccountImportStatusEnum::Read)
            ->exists();

        if ($isWaitingForADecision) {
            return Response::deny(__('A file has been read and waits for you to start it or discard it.'));
        }

        $isRunningOne = AccountImport::query()
            ->where('account_id', $user->account_id)
            ->whereIn('status', [
                AccountImportStatusEnum::Reading,
                AccountImportStatusEnum::Writing,
                AccountImportStatusEnum::RollingBack,
            ])
            ->exists();

        if ($isRunningOne) {
            return Response::deny(__('An import is already running.'));
        }

        return Response::allow();
    }
}
