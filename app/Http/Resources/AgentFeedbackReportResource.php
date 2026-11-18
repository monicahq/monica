<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * An agent feedback report as an administrator reaches it through the API.
 *
 * @mixin AgentFeedbackReport
 */
class AgentFeedbackReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'agent_feedback_report',
            'id' => $this->uuid,
            'attributes' => [
                'number' => $this->id,
                'status' => $this->status->value,
                'kind' => $this->kind->value,
                'severity' => $this->severity->value,
                'title' => $this->title,
                'tool' => $this->tool,
                'request' => $this->request,
                'steps' => $this->steps ?? [],
                'observed' => $this->observed,
                'expected' => $this->expected,
                'proposed_fix' => $this->proposed_fix,
                'proposed_code' => $this->proposed_code,
                'model' => $this->model,
                'connection_name' => $this->connection_name,
                'account_id' => $this->account_id,
                'user' => $this->user === null ? null : [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
                'submitted_at' => $this->created_at->timestamp,
                'notes' => $this->whenLoaded('notes', fn (): array => $this->notes
                    ->map(fn (AgentFeedbackNote $note): array => [
                        'id' => $note->uuid,
                        'author_name' => $note->author_name,
                        'body' => $note->body,
                        'created_at' => $note->created_at->timestamp,
                    ])
                    ->values()
                    ->all()),
            ],
            'links' => [
                'self' => route('api.v1.admin.agentFeedback.show', $this->uuid),
            ],
        ];
    }
}
