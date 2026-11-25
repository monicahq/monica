<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use App\Helpers\FieldCompletion;
use App\Helpers\RecordKind;
use App\Http\Controllers\Controller;
use App\Http\Resources\FieldCompletionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class FieldCompletionController extends Controller
{
    public function show(Request $request, string $vault, string $recordKind): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $kind = RecordKind::fromApiSegment(vault: $vault, segment: $recordKind);

        $validated = $request->validate([
            'template' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:'.FieldCompletion::LOWEST_FIRST.','.FieldCompletion::FORM_ORDER],
        ]);

        $template = $kind->template($validated['template']);
        $version = isset($validated['version']) ? (int) $validated['version'] : null;

        return new FieldCompletionResource([
            'template' => $template,
            'version' => $version,
            'completion' => new FieldCompletion(
                kind: $kind,
                template: $template,
                versionNumber: $version,
                order: $validated['order'] ?? FieldCompletion::LOWEST_FIRST,
                namesSensitiveFields: $this->reachesSensitiveFields($request->user()),
            )->toArray(),
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
