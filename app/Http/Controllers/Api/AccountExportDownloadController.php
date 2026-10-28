<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DownloadAccountExport;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportDownloadController extends Controller
{
    public function show(Request $request, string $accountExport): StreamedResponse
    {
        abort_unless(
            $this->reachesSensitiveFields($request->user()),
            403,
            __('An export holds every sensitive value, so it needs an API key with the sensitive fields scope.'),
        );

        $export = AccountExport::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $accountExport)
            ->firstOrFail();

        new DownloadAccountExport(
            user: $request->user(),
            export: $export,
        )->execute();

        return Storage::disk($export->disk)->download($export->path, basename($export->path), [
            'Content-Type' => 'application/json',
        ]);
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
