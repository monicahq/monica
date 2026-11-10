<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ReminderOccurrence */
class ReminderOccurrenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $rule = $this->reminder;
        $record = $rule?->remindable;

        return [
            'type' => 'reminder_occurrence',
            'id' => $this->uuid,
            'attributes' => [
                'reminder' => $rule?->uuid,
                'name' => $rule?->name,
                'group' => $record instanceof Group ? [
                    'id' => $record->uuid,
                    'name' => $record->name,
                ] : null,
                'date' => $this->local_date->format('Y-m-d'),
                'scheduled_for' => $this->scheduled_for->timestamp,
                'category' => $this->resource->isActive()
                    ? ReminderOccurrenceCategoryEnum::for($this->resource, CarbonImmutable::now($request->user()->timezone ?? 'UTC'))->value
                    : null,
                'answer' => $this->answer?->value,
                'answered_at' => $this->answered_at?->timestamp,
                'cancelled_reason' => $this->cancelled_reason?->value,
            ],
            'links' => [
                'reminder' => match (true) {
                    $rule === null, $rule->deleted_at !== null => null,
                    $record instanceof Contact => route('api.v1.contacts.reminders.show', [$this->vault, $record, $rule]),
                    $record instanceof Group => route('api.v1.groups.reminders.show', [$this->vault, $record->uuid, $rule]),
                    default => null,
                },
            ],
        ];
    }
}
