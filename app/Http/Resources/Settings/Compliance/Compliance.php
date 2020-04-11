<?php

namespace App\Http\Resources\Settings\Compliance;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Settings\Term>
 */
class Compliance extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'term',
            'term_version' => $this->term_version,
            'term_content' => $this->term_content,
            'privacy_version' => $this->privacy_version,
            'privacy_content' => $this->privacy_content,
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
