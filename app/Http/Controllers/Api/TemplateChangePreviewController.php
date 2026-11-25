<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateTemplateChange;
use App\Enums\ApiKeyScopeEnum;
use App\Helpers\BulkTemplateChangePreview;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Http\Controllers\Controller;
use App\Http\Resources\BulkTemplateChangePreviewResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class TemplateChangePreviewController extends Controller
{
    public function show(Request $request, string $vault, string $recordKind): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $kind = RecordKind::fromApiSegment(vault: $vault, segment: $recordKind);

        $validated = $request->validate([
            'from' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'to' => ['required', 'string', 'uuid'],
        ]);

        $source = $kind->template($validated['from']);
        $target = $kind->template($validated['to']);
        $refusal = CreateTemplateChange::refusal($kind, $source, $target);

        if ($refusal !== null) {
            throw ValidationException::withMessages(['to' => $refusal]);
        }

        $batch = new TemplateChangeBatch(
            kind: $kind,
            source: $source,
            versionNumbers: isset($validated['version']) ? [(int) $validated['version']] : [],
            target: $target->structure->publishedVersion,
        );
        $preview = new BulkTemplateChangePreview(batch: $batch, namesSensitiveFields: $this->reachesSensitiveFields($request->user()))->toArray();

        return new BulkTemplateChangePreviewResource([
            'source' => $source,
            'target' => $target,
            'blockedIds' => $kind->recordQuery()->whereIn('id', $preview['blocked'])->pluck('uuid')->all(),
            'preview' => $preview,
        ])->response()->setStatusCode(200);
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
