<?php

namespace App\Http\Resources;

use App\Enums\ImportJobStatus;
use App\Models\ImportError;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ImportJob
 */
class ImportJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {

        $etaSeconds = 0;
        if ($this->status === ImportJobStatus::PROCESSING && $this->started_at) {
            $elapsed = max(1, abs((int) now()->diffInSeconds($this->started_at, false)));
            $processed = $this->processed_rows + $this->failed_rows;
            if ($processed > 0 && $this->total_rows > $processed) {
                $rate = $processed / $elapsed;
                $remainingRows = $this->total_rows - $processed;
                $etaSeconds = (int) round($remainingRows / $rate);
            }
        }

        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'file_path' => $this->file_path,
            'file_hash' => $this->file_hash,
            'status' => $this->status->value,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'failed_rows' => $this->failed_rows,
            'progress_pct' => (float) $this->progress_pct,
            'progress_percentage' => (float) $this->progress_pct,
            'errors' => $this->errors()
                ->orderBy('row_number')
                ->limit(10)
                ->get()
                ->map(function (ImportError $error): array {
                    return [
                        'row' => $error->row_number,
                        'row_number' => $error->row_number,
                        'data' => $error->row_data ?? [],
                        'row_data' => $error->row_data ?? [],
                        'message' => $error->error_message,
                        'error_message' => $error->error_message,
                    ];
                })
                ->toArray(),
            'started_at' => $this->started_at ? $this->started_at->toIso8601String() : null,
            'cancelled_at' => $this->cancelled_at ? $this->cancelled_at->toIso8601String() : null,
            'estimated_remaining_sec' => $etaSeconds,
            'estimated_remaining_seconds' => $etaSeconds,
            'completed_at' => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'last_heartbeat_at' => $this->last_heartbeat_at ? $this->last_heartbeat_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
