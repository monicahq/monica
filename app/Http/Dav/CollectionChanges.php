<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\ContactGroupingEnum;
use App\Models\Contact;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CollectionChanges
{
    /** @param  Closure(Contact): array<int, string>  $urisFor */
    public function __construct(
        private readonly Vault $vault,
        private readonly Closure $urisFor,
    ) {}

    public function token(): string
    {
        $written = $this->timestamp(Contact::query()->withTrashed(), 'updated_at');
        $removed = $this->timestamp(Contact::query()->onlyTrashed(), 'deleted_at');

        return (string) max($written, $removed);
    }

    /** @return Collection<int, Contact> */
    public function members(): Collection
    {
        return $this->visible()->with(['fieldValues.field', 'avatar'])->get();
    }

    /**
     * A change at the token's own second is handed over again rather than
     * skipped, since two changes can share a second and only one of them would
     * otherwise be told.
     *
     * @return array{syncToken: string, added: array<int, string>, modified: array<int, string>, deleted: array<int, string>}
     */
    public function since(?string $syncToken): array
    {
        $token = $this->token();

        if ($syncToken === null || $syncToken === '') {
            return [
                'syncToken' => $token,
                'added' => $this->urisOf($this->visible()->get()),
                'modified' => [],
                'deleted' => [],
            ];
        }

        $since = CarbonImmutable::createFromTimestamp((int) $syncToken);

        return [
            'syncToken' => $token,
            'added' => $this->urisOf($this->visible()->where('created_at', '>=', $since)->get()),
            'modified' => $this->urisOf($this->visible()
                ->where('updated_at', '>=', $since)
                ->where('created_at', '<', $since)
                ->get()),
            'deleted' => $this->urisOf($this->gone($since)),
        ];
    }

    /** @return Builder<Contact> */
    private function visible(): Builder
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone);
    }

    /** @return Collection<int, Contact> */
    private function gone(CarbonImmutable $since): Collection
    {
        return Contact::query()
            ->withTrashed()
            ->where('vault_id', $this->vault->id)
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $trashed): Builder => $trashed
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $since))
                ->orWhere(fn (Builder $hidden): Builder => $hidden
                    ->whereNull('deleted_at')
                    ->where('updated_at', '>=', $since)
                    ->where(fn (Builder $state): Builder => $state
                        ->where('is_archived', true)
                        ->orWhere('is_partial', true))))
            ->get();
    }

    /**
     * @param  Collection<int, Contact>  $contacts
     * @return array<int, string>
     */
    private function urisOf(Collection $contacts): array
    {
        return $contacts
            ->flatMap(fn (Contact $contact): array => ($this->urisFor)($contact))
            ->values()
            ->all();
    }

    /** @param  Builder<Contact>  $query */
    private function timestamp(Builder $query, string $column): int
    {
        $latest = $query->where('vault_id', $this->vault->id)->max($column);

        return $latest === null ? 0 : CarbonImmutable::parse($latest)->getTimestamp();
    }
}
