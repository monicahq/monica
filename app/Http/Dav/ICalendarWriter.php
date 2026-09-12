<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;

class ICalendarWriter
{
    public function recurringDate(Contact $contact, FieldValue $value): string
    {
        $date = $this->dateOf($value);

        $calendar = new VCalendar;
        $event = $this->componentOf($calendar, 'VEVENT', [
            'UID' => $this->dateUid($contact, $value),
            'SUMMARY' => __(':name · :label', ['name' => $contact->name, 'label' => $value->field->label]),
            'RRULE' => 'FREQ=YEARLY',
            'TRANSP' => 'TRANSPARENT',
        ]);

        $event->add('DTSTART', $date->toDateTime(), ['VALUE' => 'DATE']);
        $event->add('DTEND', $date->addDay()->toDateTime(), ['VALUE' => 'DATE']);

        return $calendar->serialize();
    }

    public function reminder(ReminderOccurrence $occurrence): string
    {
        $calendar = new VCalendar;
        $task = $this->componentOf($calendar, 'VTODO', [
            'UID' => $occurrence->uuid,
            'SUMMARY' => $this->summaryOf($occurrence),
            'DESCRIPTION' => $this->aboutWhom($occurrence) ?? '',
        ]);

        $task->add('DUE', $occurrence->scheduled_for->utc()->toDateTime());

        return $calendar->serialize();
    }

    /** @param  array<string, string>  $properties */
    private function componentOf(VCalendar $calendar, string $name, array $properties): Component
    {
        $component = $calendar->add($name, $properties);

        if (! $component instanceof Component) {
            throw new \UnexpectedValueException('A calendar component was expected.');
        }

        return $component;
    }

    public function dateUid(Contact $contact, FieldValue $value): string
    {
        return $contact->uuid.'-'.$value->id;
    }

    private function dateOf(FieldValue $value): CarbonImmutable
    {
        $date = $value->value_date ?? CarbonImmutable::now();

        if (! $value->year_is_unknown) {
            return CarbonImmutable::parse($date);
        }

        return CarbonImmutable::parse($date)->setYear((int) CarbonImmutable::now()->format('Y'));
    }

    private function summaryOf(ReminderOccurrence $occurrence): string
    {
        $name = $occurrence->reminder?->name;

        if ($name !== null && mb_trim($name) !== '') {
            return $name;
        }

        return $this->aboutWhom($occurrence) ?? __('A reminder');
    }

    private function aboutWhom(ReminderOccurrence $occurrence): ?string
    {
        $reminder = $occurrence->reminder;

        return $reminder === null ? null : $reminder->remindable->name;
    }
}
