<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Override;

/** @mixin LifeEvent */
class LifeEventResource extends JsonResource
{
    /** @var list<string> */
    public const array RELATIONS = [
        'vault',
        'lifeEventType',
        'group',
        'participants.participant',
        'fieldValues.field',
        'fieldValues.valueListItem',
        'fieldValues.file',
        'fieldValues.relatedContact',
        'fieldValues.relatedEntity',
    ];

    public bool $withSensitive = false;

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $reader = $request->user() ?? Auth::user();

        if (! $reader instanceof User) {
            throw new AuthenticationException;
        }

        $formatter = new FieldValueFormatter($reader);

        return [
            'type' => 'life_event',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'title' => $this->title,
                'life_event_type' => [
                    'id' => $this->lifeEventType->uuid,
                    'name' => $this->lifeEventType->name,
                ],
                'group' => $this->group === null || $this->group->trashed() ? null : [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                ],
                'happened_on' => $this->happened_on->format('Y-m-d'),
                'duration_in_minutes' => $this->duration_in_minutes,
                'narrative' => $this->narrative,
                'location' => $this->location,
                'participants' => $this->participants
                    ->filter(fn (LifeEventParticipant $participation): bool => $participation->participant instanceof Contact)
                    ->map(fn (LifeEventParticipant $participation): array => [
                        'id' => $participation->participant->uuid,
                        'name' => $participation->participant->name,
                        'position' => $participation->position,
                    ])
                    ->values()
                    ->all(),
                'fields' => $this->fieldValues
                    ->reject(fn (FieldValue $value): bool => $value->is_detached)
                    ->map(fn (FieldValue $value): array => [
                        'field_id' => (string) $value->field_id,
                        'key' => $value->field->key,
                        'label' => $value->field->label,
                        'type' => $value->field->type->value,
                        'row' => $value->field_value_row_id,
                        'value' => $value->field->is_sensitive && ! $this->withSensitive ? null : $formatter->format($value),
                        'is_hidden' => $value->field->is_sensitive && ! $this->withSensitive,
                    ])
                    ->values()
                    ->all(),
                'created_from' => $this->created_from->value,
                'deleted_at' => $this->deleted_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.lifeEvents.show', [$this->vault, $this->resource]),
                'web' => $this->webLocation(),
            ],
        ];
    }

    /** A life event has no page of its own: it is read on the timeline of the contact it is recorded for. */
    private function webLocation(): ?string
    {
        $subject = $this->participants->first()?->participant;

        if (! $subject instanceof Contact) {
            return null;
        }

        return route('contacts.show', [$this->vault, $subject]);
    }
}
