<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\Contact;
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
        $contact = $rule?->remindable;

        return [
            'type' => 'reminder_occurrence',
            'id' => $this->uuid,
            'attributes' => [
                'reminder' => $rule?->uuid,
                'name' => $rule?->name,
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
                'reminder' => $rule !== null && $rule->deleted_at === null && $contact instanceof Contact
                    ? route('api.v1.contacts.reminders.show', [$this->vault, $contact, $rule])
                    : null,
            ],
        ];
    }
}
