<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $progressPct = 0;
        if ($this->total_rows > 0) {
            $progressPct = (int) round((($this->processed_rows + $this->failed_rows) / $this->total_rows) * 100);
        }

        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'failed_rows' => $this->failed_rows,
            'status' => $this->status,
            'progress_pct' => $progressPct,
            'started_at' => $this->started_at ? $this->started_at->toIso8601String() : null,
            'completed_at' => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
