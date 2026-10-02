<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * One record a search found, with where the words were found in it.
 *
 * @mixin Contact|Entity|LifeEvent
 */
class SearchResultResource extends JsonResource
{
    /** @var list<string> */
    public array $words = [];

    public bool $withSensitive = false;

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Contact|Entity|LifeEvent $record */
        $record = $this->resource;
        $matched = $record->matchedField($this->words, $this->withSensitive);

        return [
            'type' => match (true) {
                $record instanceof Contact => 'contact',
                $record instanceof Entity => 'entity',
                default => 'life_event',
            },
            'id' => $record->uuid,
            'name' => $record->searchableName(),
            'vault' => ['id' => $record->vault->uuid, 'name' => $record->vault->name],
            'archived' => ! $record instanceof LifeEvent && $record->is_archived,
            'date' => $record instanceof LifeEvent ? $record->happened_on->format('Y-m-d') : null,
            'matched' => $matched,
        ];
    }
}
