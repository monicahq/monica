<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateChangeResource;
use App\Models\TemplateChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TemplateChangeController extends Controller
{
    public function create(Request $request, string $vault, string $recordKind): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $kind = RecordKind::fromApiSegment(vault: $vault, segment: $recordKind);

        $validated = $request->validate([
            'from' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'to' => ['required', 'string', 'uuid'],
        ]);

        try {
            $templateChange = new CreateTemplateChange(
                user: $request->user(),
                kind: $kind,
                source: $kind->template($validated['from']),
                versionNumbers: isset($validated['version']) ? [(int) $validated['version']] : [],
                target: $kind->template($validated['to']),
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['to' => $e->getMessage()]);
        }

        return new TemplateChangeResource($templateChange)->response()->setStatusCode(202);
    }

    public function show(Request $request, string $vault, string $templateChange): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();

        $templateChange = TemplateChange::query()
            ->where('account_id', $request->user()->account_id)
            ->where('vault_id', $vault->id)
            ->where('uuid', $templateChange)
            ->firstOrFail();

        return new TemplateChangeResource($templateChange)->response()->setStatusCode(200);
    }
}
