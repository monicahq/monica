<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\VaultReminderDate;
use App\Helpers\VaultReminderDates;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Group;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin VaultReminderDate */
class VaultReminderDateResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $occurrence = $this->occurrence;

        if (! $occurrence instanceof ReminderOccurrence) {
            return $this->birthday($request);
        }

        $base = new ReminderOccurrenceResource($occurrence)->toArray($request);
        $base['attributes']['subject'] = $this->subject();
        $base['attributes']['age'] = $this->age;
        $base['links']['answer'] = $this->answerLink($occurrence);

        return $base;
    }

    /** @return array<string, mixed> */
    private function birthday(Request $request): array
    {
        $contact = $this->resource->subject;

        return [
            'type' => 'birthday',
            'id' => null,
            'attributes' => [
                'date' => $this->date->format('Y-m-d'),
                'category' => VaultReminderDates::categoryOf($this->resource, CarbonImmutable::now($request->user()->timezone ?? 'UTC')->startOfDay()),
                'subject' => $this->subject(),
                'age' => $this->age,
            ],
            'links' => [
                'contact' => $contact instanceof Contact ? route('api.v1.contacts.show', [$contact->vault, $contact]) : null,
            ],
        ];
    }

    /** @return array{type: string, id: string, name: string}|null */
    private function subject(): ?array
    {
        $subject = $this->resource->subject;

        return match (true) {
            $subject instanceof Contact => ['type' => 'contact', 'id' => $subject->uuid, 'name' => $subject->name],
            $subject instanceof Group => ['type' => 'group', 'id' => $subject->uuid, 'name' => $subject->name],
            $subject instanceof Entity => ['type' => 'entity', 'id' => $subject->uuid, 'name' => $subject->name],
            default => null,
        };
    }

    private function answerLink(ReminderOccurrence $occurrence): ?string
    {
        if (! $occurrence->exists || ! $occurrence->isActive()) {
            return null;
        }

        $subject = $this->resource->subject;

        return match (true) {
            $subject instanceof Contact => route('api.v1.contacts.reminderOccurrences.answer.update', [$occurrence->vault, $subject, $occurrence->uuid]),
            $subject instanceof Group => route('api.v1.groups.reminderOccurrences.answer.update', [$occurrence->vault, $subject->uuid, $occurrence->uuid]),
            default => null,
        };
    }
}
