<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\ContactGroupingEnum;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
use Override;
use Sabre\CalDAV\Backend\BackendInterface;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDavPlugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;

class CalendarBackend implements BackendInterface, SyncSupport
{
    public const string DATES = 'dates';

    public const string REMINDERS = 'reminders';

    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getCalendarsForUser($principalUri): array
    {
        return $this->vaults()
            ->flatMap(fn (Vault $vault): array => [
                $this->calendar($vault, self::DATES, $principalUri),
                $this->calendar($vault, self::REMINDERS, $principalUri),
            ])
            ->all();
    }

    #[Override]
    public function createCalendar($principalUri, $calendarUri, array $properties): void
    {
        throw new Forbidden('A calendar here is a vault, and a vault is made in the application.');
    }

    #[Override]
    public function updateCalendar($calendarId, PropPatch $propPatch): void
    {
        $propPatch->handleRemaining(fn (): bool => false);
    }

    #[Override]
    public function deleteCalendar($calendarId): void
    {
        throw new Forbidden('A calendar here is a vault, and a vault is deleted in the application.');
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getCalendarObjects($calendarId): array
    {
        return array_map(
            fn (array $entry): array => $this->row($entry),
            $this->entriesIn($calendarId),
        );
    }

    /** @return array<string, mixed>|null */
    #[Override]
    public function getCalendarObject($calendarId, $objectUri)
    {
        foreach ($this->entriesIn($calendarId) as $entry) {
            if ($entry['uri'] === $objectUri) {
                return $this->row($entry, withCalendar: true);
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getMultipleCalendarObjects($calendarId, array $uris): array
    {
        $wanted = array_filter(
            $this->entriesIn($calendarId),
            fn (array $entry): bool => in_array($entry['uri'], $uris, true),
        );

        return array_values(array_map(
            fn (array $entry): array => $this->row($entry, withCalendar: true),
            $wanted,
        ));
    }

    #[Override]
    public function createCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        throw new Forbidden('This calendar is read only. A date is corrected on the record it belongs to.');
    }

    #[Override]
    public function updateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        throw new Forbidden('This calendar is read only. A date is corrected on the record it belongs to.');
    }

    #[Override]
    public function deleteCalendarObject($calendarId, $objectUri): void
    {
        throw new Forbidden('This calendar is read only. Dismissing and postponing are gestures of the application.');
    }

    /** @return array<int, string> */
    #[Override]
    public function calendarQuery($calendarId, array $filters): array
    {
        return array_column($this->entriesIn($calendarId), 'uri');
    }

    #[Override]
    public function getCalendarObjectByUID($principalUri, $uid): ?string
    {
        foreach ($this->getCalendarsForUser($principalUri) as $calendar) {
            foreach ($this->entriesIn($calendar['id']) as $entry) {
                if ($entry['uid'] === $uid) {
                    return $calendar['uri'].'/'.$entry['uri'];
                }
            }
        }

        return null;
    }

    /** @return array{syncToken: string, added: array<int, string>, modified: array<int, string>, deleted: array<int, string>}|null */
    #[Override]
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null): ?array
    {
        [$vault, $kind] = $this->readTheId($calendarId);

        if ($kind === self::DATES) {
            return $this->changesInDates($vault)->since($syncToken);
        }

        return [
            'syncToken' => $this->reminderToken($vault),
            'added' => array_column($this->entriesIn($calendarId), 'uri'),
            'modified' => [],
            'deleted' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function calendar(Vault $vault, string $kind, mixed $principalUri): array
    {
        $token = $kind === self::DATES
            ? $this->changesInDates($vault)->token()
            : $this->reminderToken($vault);

        return [
            'id' => $vault->id.':'.$kind,
            'uri' => $vault->uuid.'-'.$kind,
            'principaluri' => $principalUri,
            '{DAV:}displayname' => $kind === self::DATES
                ? __(':vault · dates', ['vault' => $vault->name])
                : __(':vault · reminders', ['vault' => $vault->name]),
            '{http://calendarserver.org/ns/}getctag' => $token,
            '{http://sabredav.org/ns}sync-token' => $token,
            '{'.CalDavPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(
                [$kind === self::DATES ? 'VEVENT' : 'VTODO'],
            ),
            '{'.CalDavPlugin::NS_CALDAV.'}calendar-timezone' => null,
            '{DAV:}resourcetype' => null,
        ];
    }

    /** @return array<int, array{uid: string, uri: string, lastmodified: int|null, component: string, calendar: Closure(): string}> */
    private function entriesIn(mixed $calendarId): array
    {
        [$vault, $kind] = $this->readTheId($calendarId);

        return $kind === self::DATES
            ? $this->dateEntries($vault)
            : $this->reminderEntries($vault);
    }

    /** @return array<int, array{uid: string, uri: string, lastmodified: int|null, component: string, calendar: Closure(): string}> */
    private function dateEntries(Vault $vault): array
    {
        $writer = new ICalendarWriter;
        $entries = [];

        foreach ($this->members($vault) as $contact) {
            foreach ($contact->fieldValues as $value) {
                if (! $this->isARecurringDateADeviceCanShow($value)) {
                    continue;
                }

                $entries[] = [
                    'uid' => $writer->dateUid($contact, $value),
                    'uri' => $writer->dateUid($contact, $value).'.ics',
                    'lastmodified' => $contact->updated_at?->getTimestamp(),
                    'component' => 'vevent',
                    'calendar' => fn (): string => $writer->recurringDate($contact, $value),
                ];
            }
        }

        return $entries;
    }

    private function isARecurringDateADeviceCanShow(FieldValue $value): bool
    {
        return $value->field->type === FieldTypeEnum::RecurringDate
            && $value->value_date !== null
            && ! $value->month_is_unknown
            && ! $value->field->is_sensitive;
    }

    /** @return array<int, array{uid: string, uri: string, lastmodified: int|null, component: string, calendar: Closure(): string}> */
    private function reminderEntries(Vault $vault): array
    {
        $writer = new ICalendarWriter;
        $entries = [];

        $occurrences = ReminderOccurrence::query()
            ->upcomingInVault($vault, $this->horizon())
            ->with(['reminder.remindable'])
            ->get();

        foreach ($occurrences as $occurrence) {
            $entries[] = [
                'uid' => $occurrence->uuid,
                'uri' => $occurrence->uuid.'.ics',
                'lastmodified' => $occurrence->updated_at?->getTimestamp(),
                'component' => 'vtodo',
                'calendar' => fn (): string => $writer->reminder($occurrence),
            ];
        }

        return $entries;
    }

    /**
     * @param  array{uid: string, uri: string, lastmodified: int|null, component: string, calendar: Closure(): string}  $entry
     * @return array<string, mixed>
     */
    private function row(array $entry, bool $withCalendar = false): array
    {
        $row = [
            'id' => $entry['uid'],
            'uri' => $entry['uri'],
            'lastmodified' => $entry['lastmodified'],
            'component' => $entry['component'],
        ];

        if (! $withCalendar) {
            $row['etag'] = '"'.md5((string) $entry['lastmodified'].$entry['uid'].app()->getLocale()).'"';

            return $row;
        }

        $row['calendardata'] = ($entry['calendar'])();
        $row['etag'] = '"'.md5($row['calendardata']).'"';
        $row['size'] = strlen($row['calendardata']);

        return $row;
    }

    private function changesInDates(Vault $vault): CollectionChanges
    {
        $writer = new ICalendarWriter;

        return new CollectionChanges($vault, fn (Contact $contact): array => $contact->fieldValues
            ->filter(fn (FieldValue $value): bool => $this->isARecurringDateADeviceCanShow($value))
            ->map(fn (FieldValue $value): string => $writer->dateUid($contact, $value).'.ics')
            ->values()
            ->all());
    }

    private function reminderToken(Vault $vault): string
    {
        $latest = ReminderOccurrence::query()
            ->where('vault_id', $vault->id)
            ->max('updated_at');

        return (string) ($latest === null ? 0 : CarbonImmutable::parse($latest)->getTimestamp());
    }

    private function horizon(): CarbonImmutable
    {
        return CarbonImmutable::now()->addDays((int) config('monica.reminder_generation_window_in_days'));
    }

    /** @return Collection<int, Contact> */
    private function members(Vault $vault): Collection
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone)
            ->with(['fieldValues.field'])
            ->get();
    }

    /** @return array{0: Vault, 1: string} */
    private function readTheId(mixed $calendarId): array
    {
        $parts = explode(':', (string) (is_array($calendarId) ? ($calendarId[0] ?? '') : $calendarId));
        $vault = $this->vaults()->firstWhere('id', (int) ($parts[0] ?? 0));

        if ($vault === null || ! in_array($parts[1] ?? '', [self::DATES, self::REMINDERS], true)) {
            throw new NotFound('No calendar of yours lives there.');
        }

        return [$vault, $parts[1]];
    }

    /** @return Collection<int, Vault> */
    private function vaults(): Collection
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('name')
            ->get();
    }
}
