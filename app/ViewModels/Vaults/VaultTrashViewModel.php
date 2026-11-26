<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\TrashItemKindEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PageSize;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class VaultTrashViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?TrashItemKindEnum $kind,
        private readonly int|string $size = self::PER_PAGE,
        private readonly int $page = 1,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('Vault settings');
    }

    public function trashCount(): int
    {
        return $this->counts()['total'];
    }

    public function holdsNothing(): bool
    {
        return $this->counts()['kinds'] === [];
    }

    public function emptyLine(): string
    {
        return __('When you delete a person, a group or anything written about somebody, it waits here for 30 days in case you change your mind.');
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /** @return LengthAwarePaginator<int, TrashItem> */
    public function items(): LengthAwarePaginator
    {
        return once(function (): LengthAwarePaginator {
            $perPage = $this->size === PageSize::ALL ? max($this->trashCount(), 1) : (int) $this->size;

            return $this->trash()
                ->paginate(kind: $this->kind, perPage: $perPage, page: $this->page)
                ->withPath(route('vaults.trash.index', ['vault' => $this->vault, 'kind' => $this->kind?->value]))
                ->withQueryString();
        });
    }

    /**
     * @return list<array{key: string, label: string, icon: string, count: int, url: string, checked: bool}>
     */
    public function menu(): array
    {
        $counts = $this->counts();

        $entries = [[
            'key' => 'all',
            'label' => __('Everything'),
            'icon' => 'layers',
            'count' => $counts['total'],
            'url' => route('vaults.trash.index', ['vault' => $this->vault]),
            'checked' => $this->kind === null,
        ]];

        foreach (TrashItemKindEnum::cases() as $kind) {
            if (! isset($counts['kinds'][$kind->value])) {
                continue;
            }

            $entries[] = [
                'key' => $kind->value,
                'label' => __($kind->pluralLabel()),
                'icon' => $kind->icon(),
                'count' => $counts['kinds'][$kind->value],
                'url' => route('vaults.trash.index', ['vault' => $this->vault, 'kind' => $kind->value]),
                'checked' => $this->kind === $kind,
            ];
        }

        return $entries;
    }

    /** @return array{key: string, label: string, icon: string, count: int, url: string, checked: bool} */
    public function currentEntry(): array
    {
        $menu = $this->menu();

        foreach ($menu as $entry) {
            if ($entry['checked']) {
                return $entry;
            }
        }

        return $menu[0];
    }

    public function nothingOfThisKindLine(): string
    {
        return __('Nothing of this kind is in the trash.');
    }

    /**
     * @return list<array{kind: string, uuid: string, title: string, typeLabel: string, context: string|null, icon: string, isPerson: bool, deletedAgo: string, daysLeft: int, daysLeftLabel: string, isUrgent: bool, tooltip: string, obstacle: string|null, obstacleId: string, restoreUrl: string, destroyUrl: string, destroyTitle: string, destroyLines: list<string>, keepLabel: string}>
     */
    public function rows(): array
    {
        $items = $this->items()->getCollection();
        $dependents = $this->trash()->dependentCounts($items);

        return $items->map(function (TrashItem $item) use ($dependents): array {
            $withIt = $dependents["{$item->record->getMorphClass()}:{$item->record->getKey()}"] ?? [];
            $daysLeft = $item->daysLeft();
            $id = "{$item->kind->value}-{$item->uuid()}";

            return [
                'kind' => $item->kind->value,
                'uuid' => $item->uuid(),
                'title' => $item->title(),
                'typeLabel' => $item->typeLabel(),
                'context' => $item->context(),
                'icon' => $item->kind->icon(),
                'isPerson' => $item->kind === TrashItemKindEnum::Contact,
                'deletedAgo' => $this->ago($item->deletedAt()),
                'daysLeft' => $daysLeft,
                'daysLeftLabel' => trans_choice(':count day left|:count days left', $daysLeft),
                'isUrgent' => $daysLeft <= 3,
                'tooltip' => $item->expiresAt()->isTomorrow()
                    ? trans_choice(':count day left. Deleted for good tomorrow, on :date.|:count days left. Deleted for good tomorrow, on :date.', $daysLeft, ['date' => $this->writeDate($item->expiresAt())])
                    : trans_choice(':count day left. Deleted for good on :date.|:count days left. Deleted for good on :date.', $daysLeft, ['date' => $this->writeDate($item->expiresAt())]),
                'obstacle' => $item->obstacle(),
                'obstacleId' => "trash-obstacle-{$id}",
                'restoreUrl' => route('vaults.trash.restoration.update', ['vault' => $this->vault, 'kind' => $item->kind->value, 'item' => $item->uuid()]),
                'destroyUrl' => route('vaults.trash.destroy', ['vault' => $this->vault, 'kind' => $item->kind->value, 'item' => $item->uuid()]),
                'destroyTitle' => __('Delete “:name” for good?', ['name' => $item->title()]),
                'destroyLines' => $withIt === [] ? [] : [__('What was deleted at the same time goes with it: :things.', ['things' => TrashItemKindEnum::countsPhrase($withIt)])],
                'keepLabel' => $withIt === [] ? __('Keep it') : __('Keep them'),
            ];
        })->all();
    }

    public function emptyUrl(): string
    {
        return route('vaults.trash.empty', ['vault' => $this->vault]);
    }

    /** @return list<string> */
    public function emptyLines(): array
    {
        return [
            __('This deletes :things for good.', ['things' => TrashItemKindEnum::countsPhrase($this->counts()['kinds'])]),
        ];
    }

    /** @return array{total: int, kinds: array<string, int>} */
    private function counts(): array
    {
        return once(fn (): array => $this->trash()->counts());
    }

    private function trash(): VaultTrash
    {
        return once(fn (): VaultTrash => new VaultTrash(vault: $this->vault));
    }

    private function ago(CarbonInterface $deletedAt): string
    {
        $days = (int) $deletedAt->copy()->startOfDay()->diffInDays(now()->startOfDay());

        if ($days === 0) {
            return __('Today');
        }

        if ($days < 7) {
            return trans_choice('{1}Yesterday|:count days ago', $days);
        }

        return trans_choice('{1}A week ago|:count weeks ago', intdiv($days, 7));
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
