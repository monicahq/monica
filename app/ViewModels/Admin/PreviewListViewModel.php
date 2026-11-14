<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\PageSize;
use App\Models\PreviewListEntry;
use Illuminate\Pagination\LengthAwarePaginator;

class PreviewListViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, PreviewListEntry> */
    public function entries(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            PreviewListEntry::query()
                ->orderByRaw('case when registered_at is not null then 2 when admitted_at is not null then 1 else 0 end')
                ->oldest()
                ->orderBy('id'),
            $this->size,
        ));
    }

    /**
     * One row per entry, as the list says it: the address, when it joined, where it stands, and
     * what may still be done with it.
     *
     * @return list<array{id: int, email: string, joined: string, state: string, admitted: string|null, admitLabel: string|null, admitUrl: string, destroyUrl: string}>
     */
    public function rows(): array
    {
        return array_map(fn (PreviewListEntry $entry): array => [
            'id' => $entry->id,
            'email' => $entry->email,
            'joined' => __('joined :date', ['date' => $entry->created_at->isoFormat('LL')]),
            'state' => match (true) {
                $entry->registered_at !== null => __('registered'),
                $entry->admitted_at !== null => __('admitted'),
                default => __('waiting'),
            },
            'admitted' => $entry->admitted_at === null ? null : __('admitted :date', ['date' => $entry->admitted_at->isoFormat('LL')]),
            'admitLabel' => match (true) {
                $entry->registered_at !== null => null,
                $entry->admitted_at !== null => __('Admit again'),
                default => __('Admit'),
            },
            'admitUrl' => route('admin.previewList.admission.create', ['previewListEntry' => $entry->id]),
            'destroyUrl' => route('admin.previewList.destroy', ['previewListEntry' => $entry->id]),
        ], $this->entries()->items());
    }

    public function isEnabled(): bool
    {
        return (bool) config('monica.preview_list_enabled');
    }

    public function waitingCount(): int
    {
        return once(fn (): int => PreviewListEntry::query()
            ->whereNull('admitted_at')
            ->whereNull('registered_at')
            ->count());
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }
}
