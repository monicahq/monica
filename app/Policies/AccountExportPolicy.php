<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountExportPolicy
{
    public function create(User $user): Response
    {
        $isPreparingOne = AccountExport::query()
            ->where('account_id', $user->account_id)
            ->where('status', AccountExportStatusEnum::Preparing)
            ->exists();

        if ($isPreparingOne) {
            return Response::deny(__('An export is already being prepared.'));
        }

        return Response::allow();
    }

    public function download(User $user, AccountExport $accountExport): Response
    {
        if ($accountExport->status !== AccountExportStatusEnum::Ready) {
            return Response::denyAsNotFound(__('Only an export that is ready, and has not expired, can be downloaded.'));
        }

        if ($accountExport->expires_at?->isFuture() !== true) {
            return Response::denyAsNotFound(__('Only an export that is ready, and has not expired, can be downloaded.'));
        }

        return Response::allow();
    }
}
