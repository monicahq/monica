<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\DateFormatEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VaultDashboardViewModel
{
    private const int FAVOURITES_SHOWN = 10;

    private const int RECENTLY_VIEWED_SHOWN = 5;

    private const int LIFE_EVENTS_SHOWN = 5;

    private ?VaultDashboardAttention $attention = null;

    /** @var Collection<int, Contact>|null */
    private ?Collection $favouriteContacts = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return $this->vault->name;
    }

    public function description(): ?string
    {
        return $this->vault->description;
    }

    public function vaultsUrl(): string
    {
        return route('vaults.index');
    }

    public function newContactUrl(): string
    {
        return route('contacts.new', $this->vault);
    }

    /**
     * What the free offer allows, said only when the account is at it. The control stays on the
     * screen and says why, rather than disappearing and leaving nothing to understand.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Contact::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function allContactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /** @return array{initial: string, colour: string, tint: string} */
    public function tile(): array
    {
        return [
            'initial' => mb_strtoupper(mb_substr($this->vault->name, 0, 1)),
            'colour' => $this->vault->colour,
            'tint' => "color-mix(in srgb, {$this->vault->colour} 12%, var(--canvas))",
        ];
    }

    /** @return list<string> */
    public function figures(): array
    {
        return [
            trans_choice(':count person|:count people', $this->vault->contact_count),
            trans_choice(':count reminder this week|:count reminders this week', $this->attention()->count()),
            trans_choice(':count favourite|:count favourites', $this->favouriteContacts()->count()),
            trans_choice(':count life event in the last three months|:count life events in the last three months', $this->lifeEventsSince($this->today()->subMonths(3))),
        ];
    }

    public function attention(): VaultDashboardAttention
    {
        return $this->attention ??= new VaultDashboardAttention(user: $this->user, vault: $this->vault);
    }

    /** @return list<array{name: string, avatarUrl: string|null, url: string}> */
    public function favourites(): array
    {
        return $this->favouriteContacts()
            ->take(self::FAVOURITES_SHOWN)
            ->map(fn (Contact $contact): array => $this->person($contact))
            ->values()
            ->all();
    }

    public function moreFavouritesUrl(): ?string
    {
        if ($this->favouriteContacts()->count() <= self::FAVOURITES_SHOWN) {
            return null;
        }

        return route('contacts.index', ['vault' => $this->vault, 'filter' => ContactGroupingEnum::Favourites->value]);
    }

    /** @return list<array{name: string, avatarUrl: string|null, url: string, when: string}> */
    public function recentlyViewed(): array
    {
        return $this->vault
            ->contacts()
            ->listed()
            ->whereNotNull('last_consulted_at')
            ->with('avatar')
            ->orderByDesc('last_consulted_at')
            ->limit(self::RECENTLY_VIEWED_SHOWN)
            ->get()
            ->map(fn (Contact $contact): array => [
                ...$this->person($contact),
                'when' => $this->day($contact->last_consulted_at),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{title: string, icon: string, tint: string, date: string, who: string, url: string|null}> */
    public function lifeEvents(): array
    {
        return LifeEvent::query()
            ->where('account_id', $this->vault->account_id)
            ->where('vault_id', $this->vault->id)
            ->with(['lifeEventType', 'participants.participant'])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit(self::LIFE_EVENTS_SHOWN)
            ->get()
            ->map(function (LifeEvent $lifeEvent): array {
                $people = $lifeEvent->participants
                    ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
                    ->filter(fn (mixed $participant): bool => $participant instanceof Contact)
                    ->values();

                $linked = $people->first(fn (Contact $contact): bool => ! $contact->is_partial);
                $icon = $lifeEvent->lifeEventType->icon;

                return [
                    'title' => $lifeEvent->name,
                    'icon' => in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON,
                    'tint' => VaultColourEnum::tintFor($lifeEvent->lifeEventType->colour),
                    'date' => $this->lifeEventDate($lifeEvent),
                    'who' => $people->map(fn (Contact $contact): string => $contact->name)->implode(__(', ')),
                    'url' => $linked === null
                        ? null
                        : route('contacts.show', ['vault' => $this->vault, 'contact' => $linked]),
                ];
            })
            ->all();
    }

    /** @return Collection<int, Contact> */
    private function favouriteContacts(): Collection
    {
        return $this->favouriteContacts ??= $this->vault
            ->contacts()
            ->listed()
            ->where('is_favourite', true)
            ->with('avatar')
            ->get()
            ->sortByCollator(fn (Contact $contact): string => $contact->name)
            ->values();
    }

    /** @return array{name: string, avatarUrl: string|null, url: string} */
    private function person(Contact $contact): array
    {
        return [
            'name' => $contact->name,
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.show', ['vault' => $this->vault, 'file' => $contact->avatar]),
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
        ];
    }

    private function lifeEventsSince(CarbonImmutable $day): int
    {
        return LifeEvent::query()
            ->where('account_id', $this->vault->account_id)
            ->where('vault_id', $this->vault->id)
            ->whereDate('happened_on', '>=', $day->toDateString())
            ->whereDate('happened_on', '<=', $this->today()->toDateString())
            ->count();
    }

    private function lifeEventDate(LifeEvent $lifeEvent): string
    {
        $day = $lifeEvent->happened_on;

        return match ($lifeEvent->date_precision) {
            DatePrecisionEnum::Day => $this->write($day),
            DatePrecisionEnum::Month => DateFormatEnum::write($day, 'F Y'),
            DatePrecisionEnum::Year => (string) $day->year,
            DatePrecisionEnum::Decade => __('Between :start and :end', ['start' => intdiv($day->year, 10) * 10, 'end' => intdiv($day->year, 10) * 10 + 9]),
            DatePrecisionEnum::Unknown => __('Date unknown'),
        };
    }

    private function day(CarbonImmutable $moment): string
    {
        $day = $moment->setTimezone($this->user->timezone)->startOfDay();
        $daysAgo = (int) $day->diffInDays($this->today(), false);

        return match ($daysAgo) {
            0 => __('Today'),
            1 => __('Yesterday'),
            default => $this->write($day),
        };
    }

    private function write(CarbonInterface $day): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        if ($day->year === $this->today()->year) {
            $format = mb_trim((string) preg_replace('#[/.\-\s]*Y[/.\-\s]*#', ' ', $format));
        }

        return DateFormatEnum::write($day, $format);
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->user->timezone)->startOfDay();
    }
}
