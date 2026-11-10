<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ReminderForm
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact|Group $record,
        private readonly ?Reminder $reminder = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function isEditing(): bool
    {
        return $this->reminder instanceof Reminder;
    }

    public function title(): string
    {
        return $this->isEditing() ? __('Edit the reminder') : __('Add a reminder');
    }

    /**
     * The trail above the form: the person and their reminders, or the group.
     *
     * @return list<array{label: string, url?: string}>
     */
    public function breadcrumb(): array
    {
        if ($this->record instanceof Group) {
            return [
                ['label' => __('Groups'), 'url' => route('groups.index', $this->vault)],
                ['label' => $this->record->name, 'url' => $this->recordUrl()],
                ['label' => $this->title()],
            ];
        }

        return [
            ['label' => __('People'), 'url' => route('contacts.index', $this->vault)],
            ['label' => $this->record->name, 'url' => $this->recordUrl()],
            ['label' => __('Reminders'), 'url' => $this->remindersUrl()],
            ['label' => $this->title()],
        ];
    }

    public function sidebarEntry(): string
    {
        return $this->record instanceof Group ? 'groups' : 'contacts';
    }

    public function recordUrl(): string
    {
        if ($this->record instanceof Group) {
            return route('groups.show', ['vault' => $this->vault, 'group' => $this->record]);
        }

        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->record]);
    }

    public function remindersUrl(): string
    {
        if ($this->record instanceof Group) {
            return $this->recordUrl();
        }

        return route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->record]);
    }

    public function cancelUrl(): string
    {
        if ($this->reminder instanceof Reminder) {
            return route('reminders.show', ['vault' => $this->vault, 'reminder' => $this->reminder]);
        }

        return $this->remindersUrl();
    }

    public function actionUrl(): string
    {
        if ($this->record instanceof Group) {
            return $this->reminder instanceof Reminder
                ? route('groups.reminders.update', ['vault' => $this->vault, 'group' => $this->record, 'reminder' => $this->reminder])
                : route('groups.reminders.create', ['vault' => $this->vault, 'group' => $this->record]);
        }

        if ($this->reminder instanceof Reminder) {
            return route('contacts.reminders.update', ['vault' => $this->vault, 'contact' => $this->record, 'reminder' => $this->reminder]);
        }

        return route('contacts.reminders.create', ['vault' => $this->vault, 'contact' => $this->record]);
    }

    public function method(): string
    {
        return $this->isEditing() ? 'put' : 'post';
    }

    public function name(): string
    {
        return $this->reminder->name ?? '';
    }

    public function startsOn(): string
    {
        return $this->reminder?->starts_on?->format('Y-m-d')
            ?? CarbonImmutable::now($this->timezone())->addDay()->toDateString();
    }

    public function cadence(): string
    {
        return $this->reminder->cadence->value ?? ReminderCadenceEnum::Once->value;
    }

    public function interval(): int
    {
        return $this->reminder->frequency_interval ?? 1;
    }

    public function maximumInterval(): int
    {
        return Reminder::MAXIMUM_INTERVAL;
    }

    public function time(): string
    {
        return mb_substr($this->reminder->time_of_day ?? Reminder::DEFAULT_TIME, 0, 5);
    }

    public function timezone(): string
    {
        return $this->reminder->timezone ?? $this->user->timezone;
    }

    /** @return array<string, string> */
    public function cadences(): array
    {
        $cadences = [];

        foreach (ReminderCadenceEnum::cases() as $cadence) {
            $cadences[$cadence->value] = Str::ucfirst(__($cadence->label()));
        }

        return $cadences;
    }

    /** @return list<array{timezone: string, label: string}> */
    public function zones(): array
    {
        return array_map(fn (array $zone): array => [
            'timezone' => $zone['timezone'],
            'label' => $zone['country'] === '' ? $zone['city'] : "{$zone['city']}, {$zone['country']}",
        ], TimezoneCatalogue::all());
    }
}
