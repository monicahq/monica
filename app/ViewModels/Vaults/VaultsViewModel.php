<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;

class VaultsViewModel
{
    private const int FACES_SHOWN = 4;

    public function __construct(
        private readonly User $user,
    ) {}

    public function title(): string
    {
        return __('Your vaults');
    }

    public function createUrl(): string
    {
        return route('vaults.new');
    }

    /**
     * What the free offer allows, said only when the account is at it. The control stays on the
     * screen and says why, rather than disappearing and leaving nothing to understand.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Vault::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    /**
     * @return array<int, array{name: string, initial: string, colour: string, tint: string, visitLine: string, faces: array<int, array{name: string, avatarUrl: string|null}>, moreFaces: string|null, openTaskCount: int, dateCount: int, isDefault: bool, url: string}>
     */
    public function vaults(): array
    {
        $vaults = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderByRaw('last_visited_at is null')
            ->orderByDesc('last_visited_at')
            ->orderBy('position')
            ->get();

        return $vaults
            ->map(fn (Vault $vault): array => [
                'name' => $vault->name,
                'initial' => mb_strtoupper((string) grapheme_substr($vault->name, 0, 1)),
                'colour' => $vault->colour,
                'tint' => "color-mix(in srgb, {$vault->colour} 12%, var(--canvas))",
                'visitLine' => $this->visitLine($vault),
                'faces' => $this->faces($vault),
                'moreFaces' => $this->moreFaces($vault),
                'openTaskCount' => $this->openTaskCount(),
                'dateCount' => $this->dateCount($vault),
                'isDefault' => $vault->is_default,
                'url' => route('vaults.show', $vault),
            ])
            ->all();
    }

    private function visitLine(Vault $vault): string
    {
        if ($vault->last_visited_at === null) {
            return __('Not opened yet');
        }

        return __('Opened :when', [
            'when' => $vault->last_visited_at->setTimezone($this->user->timezone)->diffForHumans(),
        ]);
    }

    /** @return array<int, array{name: string, avatarUrl: string|null}> */
    private function faces(Vault $vault): array
    {
        return $vault
            ->contacts()
            ->listed()
            ->with('avatar')
            ->orderByDesc('created_at')
            ->limit(self::FACES_SHOWN)
            ->get()
            ->map(fn (Contact $contact): array => [
                'name' => $contact->name,
                'avatarUrl' => $contact->avatar === null
                    ? null
                    : route('files.variants.show', ['vault' => $vault, 'file' => $contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
            ])
            ->all();
    }

    private function moreFaces(Vault $vault): ?string
    {
        $remaining = $vault->contact_count - self::FACES_SHOWN;

        if ($remaining <= 0) {
            return null;
        }

        return trans_choice('[0,*]and :count more', $remaining);
    }

    /**
     * Always none, because nothing in the application writes a task down yet. The cell stays on the
     * card so the two counts read as a pair, and it tells the truth the day tasks arrive.
     */
    private function openTaskCount(): int
    {
        return 0;
    }

    /**
     * What the vault has to be told about before the month is out. Anything actionable and still
     * undealt with counts, overdue included, which is the set the reminders screen calls upcoming.
     */
    private function dateCount(Vault $vault): int
    {
        return ReminderOccurrence::query()
            ->awaitingAnswerInVault(
                vault: $vault,
                untilDay: CarbonImmutable::now($this->user->timezone)->endOfMonth(),
            )
            ->count();
    }

    public function user(): User
    {
        return $this->user;
    }
}
