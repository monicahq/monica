<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Field */
class FieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'field',
            'id' => (string) $this->id,
            'attributes' => [
                'key' => $this->key,
                'type' => $this->type->value,
                'label' => $this->label,
                'help_text' => $this->help_text,
                'placeholder' => $this->placeholder,
                'position' => $this->position,
                'is_required' => $this->is_required,
                'is_sensitive' => $this->is_sensitive,
                'is_highlighted' => $this->is_highlighted,
                'is_unique' => $this->is_unique,
                'is_repeatable' => $this->is_repeatable,
                'repeat_min' => $this->repeat_min,
                'repeat_max' => $this->repeat_max,
                'repetition_order' => $this->repetition_order?->value,
                'options' => $this->options,
                'validation' => $this->validation,
                'default_value' => $this->default_value,
                'relation_target' => $this->relation_target?->value,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
        ];
    }
}
