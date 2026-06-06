<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tag
 */
class TagResource extends JsonResource
{
    /**
     * The "data" wrapper that should be used around the collection.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tag_category' => $this->tag_category,
            'color' => $this->color,
            'usage_count' => $this->when(
                isset($this->usage_count),
                $this->usage_count ?? 0
            ),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
            'links' => [
                'self' => route('api.tags.show', [$this->vault_id, $this->id]),
            ],
        ];
    }
}
