<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\McpCall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Override;

/** @mixin McpCall */
class McpCallResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'mcp_call',
            'id' => $this->uuid,
            'attributes' => [
                'started_at_in_milliseconds' => (int) $this->started_at->getTimestampMs(),
                'duration_in_milliseconds' => $this->duration_in_milliseconds,
                'connection' => [
                    'type' => $this->connection_type->value,
                    'id' => $this->connection_id,
                    'name' => $this->connection_name,
                ],
                'tool' => $this->tool,
                'operation' => $this->operation?->value,
                'vault' => $this->vault_name === null ? null : [
                    'id' => $this->vault?->uuid,
                    'name' => $this->vault_name,
                ],
                'subject' => $this->subject_type === null ? null : [
                    'type' => Str::snake(class_basename($this->subject_type)),
                    'label' => $this->subject_label,
                ],
                'result' => $this->result->value,
                'error' => $this->error_code === null && $this->error_message === null ? null : [
                    'code' => $this->error_code,
                    'message' => $this->error_message,
                ],
                'json_rpc_id' => $this->json_rpc_id,
                'ip_address' => $this->ip_address,
                'parameters' => $this->parameters,
                'parameters_truncated' => $this->parameters_truncated,
                'response' => $this->response,
                'response_truncated' => $this->response_truncated,
            ],
            'links' => [
                'self' => route('api.v1.mcpCalls.show', $this->uuid),
            ],
        ];
    }
}
