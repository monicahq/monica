<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SubmitAgentFeedback;
use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class AgentFeedbackController extends Controller
{
    private const array PLACEHOLDERS = ['', 'na', 'none', 'noop', 'null', 'placeholder', 'tbd', 'test', 'todo', 'undefined'];

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::enum(AgentFeedbackKindEnum::class)],
            'severity' => ['required', Rule::enum(AgentFeedbackSeverityEnum::class)],
            'title' => ['required', 'string', 'max:200'],
            'tool' => ['nullable', 'string', 'max:100'],
            'request' => ['nullable', 'string', 'max:4000'],
            'steps' => ['nullable', 'array', 'max:20'],
            'steps.*.tool' => ['required', 'string', 'max:100'],
            'steps.*.arguments' => ['nullable', 'string', 'max:4000'],
            'steps.*.outcome' => ['required', Rule::in(['success', 'error'])],
            'steps.*.result' => ['nullable', 'string', 'max:4000'],
            'observed' => ['required', 'string', 'max:4000', function (string $attribute, mixed $value, Closure $fail): void {
                if (in_array(mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]/u', '', (string) $value)), self::PLACEHOLDERS, true)) {
                    $fail(__('Describe what you tried and what went wrong, or do not send a report when there is nothing to report.'));
                }
            }],
            'expected' => ['required', 'string', 'max:4000'],
            'proposed_fix' => ['required', 'string', 'max:4000'],
            'proposed_code' => ['nullable', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        $token = $request->user()->currentAccessToken();
        $connectionName = request()->attributes->get('mcp_connection_name') ?? ($token instanceof PersonalAccessToken ? $token->name : null);

        $report = new SubmitAgentFeedback(
            user: $request->user(),
            connectionName: is_string($connectionName) ? $connectionName : '',
            kind: AgentFeedbackKindEnum::from($validated['kind']),
            severity: AgentFeedbackSeverityEnum::from($validated['severity']),
            title: $validated['title'],
            observed: $validated['observed'],
            expected: $validated['expected'],
            proposedFix: $validated['proposed_fix'],
            tool: $validated['tool'] ?? null,
            request: $validated['request'] ?? null,
            steps: array_values($validated['steps'] ?? []),
            proposedCode: $validated['proposed_code'] ?? null,
            model: $validated['model'] ?? null,
        )->execute();

        return response()->json([
            'id' => $report->uuid,
            'status' => $report->status->value,
        ], 201);
    }
}
