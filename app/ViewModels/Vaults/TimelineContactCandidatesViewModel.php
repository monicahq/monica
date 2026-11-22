<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Query\Builder;

class TimelineContactCandidatesViewModel
{
    public const int CANDIDATE_LIMIT = 10;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query,
    ) {}

    /**
     * The people of the vault matching what was typed who took part in a life event of it, since
     * nobody else can narrow the timeline.
     *
     * @return list<array{uuid: string, name: string}>
     */
    public function candidates(): array
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->whereExists(fn (Builder $query): Builder => $query
                ->selectRaw('1')
                ->from('life_event_participants')
                ->join('life_events', 'life_events.id', '=', 'life_event_participants.life_event_id')
                ->whereColumn('life_event_participants.participant_id', 'contacts.id')
                ->where('life_event_participants.participant_type', new Contact()->getMorphClass())
                ->where('life_events.vault_id', $this->vault->id)
                ->whereNull('life_events.deleted_at'))
            ->matchingSearch($this->query, $this->user->locale)
            ->orderBy('sort_key')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Contact $contact): array => ['uuid' => $contact->uuid, 'name' => $contact->name])
            ->values()
            ->all();
    }

    public function noCandidatesLine(): string
    {
        if (mb_trim($this->query) === '') {
            return __('Nobody in this vault has a life event yet.');
        }

        return __('Nobody with a life event matches “:query”.', ['query' => $this->query]);
    }
}
