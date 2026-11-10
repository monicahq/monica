<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ReminderSchedule;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Reminder */
class ReminderResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $record = $this->remindable;
        $next = $this->is_active ? ReminderSchedule::nextDate($this->resource) : null;

        return [
            'type' => 'reminder',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'source' => $this->source->value,
                'starts_on' => $this->starts_on?->format('Y-m-d'),
                'field' => $this->field === null ? null : [
                    'key' => $this->field->key,
                    'label' => $this->field->label,
                ],
                'group' => $record instanceof Group ? [
                    'id' => $record->uuid,
                    'name' => $record->name,
                ] : null,
                'cadence' => $this->cadence->value,
                'interval' => $this->frequency_interval,
                'time' => mb_substr($this->time_of_day, 0, 5),
                'timezone' => $this->timezone,
                'is_active' => $this->is_active,
                'inactive_reason' => $this->inactive_reason?->value,
                'next_date' => $next?->format('Y-m-d'),
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => match (true) {
                    $record instanceof Contact => route('api.v1.contacts.reminders.show', [$this->vault, $record, $this->resource]),
                    $record instanceof Group => route('api.v1.groups.reminders.show', [$this->vault, $record->uuid, $this->resource]),
                    default => null,
                },
            ],
        ];
    }
}
