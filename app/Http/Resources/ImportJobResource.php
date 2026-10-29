<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ImportJob
 */
class ImportJobResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->original_filename,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'failed_rows' => $this->failed_rows,
            'status' => $this->status,
            'progress_pct' => $this->progressPct(),
            'errors' => $this->errors ?? [],
            'started_at' => $this->started_at ? DateHelper::getTimestamp($this->started_at) : null,
            'estimated_remaining_sec' => $this->estimatedRemainingSec(),
            'created_at' => DateHelper::getTimestamp($this->created_at),
        ];
    }

    private function progressPct(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    private function estimatedRemainingSec(): ?int
    {
        if ($this->status !== 'processing' || ! $this->started_at || $this->processed_rows === 0) {
            return null;
        }

        $elapsed = max(now()->diffInSeconds($this->started_at), 1);
        $remaining = $this->total_rows - $this->processed_rows;

        return (int) ceil($remaining / ($this->processed_rows / $elapsed));
    }
}
