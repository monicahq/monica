<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ContactListCatalogue;
use App\Models\ContactListColumn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ContactListColumn */
class ContactListColumnResource extends JsonResource
{
    public function __construct(
        ContactListColumn $column,
        private readonly ContactListCatalogue $catalogue,
    ) {
        parent::__construct($column);
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'source' => $this->source->value,
            'section' => $this->section?->uuid,
            'field_key' => $this->field_key,
            'heading' => $this->heading,
            'default_heading' => $this->catalogue->defaultHeadingOf($this->resource),
            'is_visible' => $this->is_visible,
            'is_supplied' => $this->catalogue->isSupplied($this->resource),
        ];
    }
}
