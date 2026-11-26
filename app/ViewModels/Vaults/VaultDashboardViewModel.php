<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\DateFormatEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VaultDashboardViewModel
{
    private const int FAVOURITES_SHOWN = 10;

    private const int RECENTLY_VIEWED_SHOWN = 5;

    private ?VaultDashboardAttention $attention = null;

    /** @var Collection<int, Contact>|null */
    private ?Collection $favouriteContacts = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly bool $opensLifeEventPanel = false,
        private readonly ?int $lifeEventJustAddedId = null,
        private readonly ?string $timelineSlice = null,
        private readonly ?string $timelineContact = null,
        private readonly ?string $timelineType = null,
        private readonly int $timelinePage = 1,
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
            'initial' => mb_strtoupper((string) grapheme_substr($this->vault->name, 0, 1)),
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
        return once(fn (): array => $this->vault
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
            ->all());
    }

    /**
     * The panel that writes down a life event, for the signed in person unless
     * somebody else is chosen. A vault without its Me contact offers none.
     */
    public function lifeEventForm(): ?LifeEventFormBlock
    {
        $me = $this->meContact();

        if (! $me instanceof Contact) {
            return null;
        }

        return once(fn (): LifeEventFormBlock => new LifeEventFormBlock(
            user: $this->user,
            vault: $this->vault,
            contact: $me,
            isAskedFor: $this->opensLifeEventPanel,
            choosesSubject: true,
        ));
    }

    public function timeline(): DashboardTimelineBlock
    {
        return once(fn (): DashboardTimelineBlock => new DashboardTimelineBlock(
            user: $this->user,
            vault: $this->vault,
            slice: $this->timelineSlice,
            contact: $this->timelineContact,
            type: $this->timelineType,
            page: $this->timelinePage,
            justAddedId: $this->lifeEventJustAddedId,
        ));
    }

    public function timelineCount(): int
    {
        return $this->timeline()->total();
    }

    /** @return list<array{key: string, label: string, icon: string, count: int}> */
    public function tabs(): array
    {
        return [
            ['key' => 'timeline', 'label' => __('Timeline'), 'icon' => 'timeline', 'count' => $this->timelineCount()],
            ['key' => 'attention', 'label' => __('Needs your attention'), 'icon' => 'reminder', 'count' => $this->attention()->count()],
            ['key' => 'favourites', 'label' => __('Favourite people'), 'icon' => 'star', 'count' => $this->favouriteContacts()->count()],
            ['key' => 'recent', 'label' => __('Recently viewed'), 'icon' => 'clock', 'count' => count($this->recentlyViewed())],
        ];
    }

    private function meContact(): ?Contact
    {
        return once(fn (): ?Contact => $this->vault
            ->contacts()
            ->where('is_me', true)
            ->first());
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
                : route('files.variants.show', ['vault' => $this->vault, 'file' => $contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
        ];
    }

    private function lifeEventsSince(CarbonImmutable $day): int
    {
        return LifeEvent::query()
            ->where('account_id', $this->vault->account_id)
            ->where('vault_id', $this->vault->id)
            ->where('happened_on', '>=', $day->toDateString())
            ->where('happened_on', '<', $this->today()->addDay()->toDateString())
            ->count();
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
