<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Helpers\TimezoneCatalogue;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ContactReminderForm
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
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

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function isEditing(): bool
    {
        return $this->reminder instanceof Reminder;
    }

    public function title(): string
    {
        return $this->isEditing() ? __('Edit the reminder') : __('Add a reminder');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function remindersUrl(): string
    {
        return route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function actionUrl(): string
    {
        if ($this->reminder instanceof Reminder) {
            return route('contacts.reminders.update', ['vault' => $this->vault, 'contact' => $this->contact, 'reminder' => $this->reminder]);
        }

        return route('contacts.reminders.create', ['vault' => $this->vault, 'contact' => $this->contact]);
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
