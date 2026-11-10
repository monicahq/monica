<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\AdministratorActionEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PageSize;
use App\Models\AdministratorLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdministratorLogsViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $viewer,
        private readonly ?int $administratorId = null,
        private readonly ?int $accountId = null,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, AdministratorLog> */
    public function entries(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            $this->matching()
                ->with(['user', 'account.user'])
                ->latest(),
            $this->size,
        ));
    }

    /** @return Collection<int, User> */
    public function administrators(): Collection
    {
        return once(fn (): Collection => User::query()
            ->whereIn('id', AdministratorLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->get()
            ->sortByCollator('first_name'));
    }

    public function administratorId(): ?int
    {
        return $this->administratorId;
    }

    public function accountId(): ?int
    {
        return $this->accountId;
    }

    public function isFiltered(): bool
    {
        return $this->administratorId !== null || $this->accountId !== null;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /** @return list<array{heading: string, summary: string, entries: list<AdministratorLog>}> */
    public function days(): array
    {
        $today = CarbonImmutable::now($this->viewer->timezone)->startOfDay();
        $days = [];

        foreach ($this->entries()->getCollection() as $entry) {
            $days[$this->moment($entry)->format('Y-m-d')][] = $entry;
        }

        $result = [];

        foreach ($days as $key => $entries) {
            $date = CarbonImmutable::parse($key, $this->viewer->timezone);
            $written = $this->writeDate($date);
            $count = trans_choice(':count action|:count actions', count($entries));

            [$heading, $summary] = match (true) {
                $date->equalTo($today) => [__('Today'), __(':date · :actions', ['date' => $written, 'actions' => $count])],
                $date->equalTo($today->subDay()) => [__('Yesterday'), __(':date · :actions', ['date' => $written, 'actions' => $count])],
                default => [$written, $count],
            };

            $result[] = ['heading' => $heading, 'summary' => $summary, 'entries' => $entries];
        }

        return $result;
    }

    /** @return list<array{text: string, kind: string, url: string|null}> */
    public function sentence(AdministratorLog $entry): array
    {
        $action = AdministratorActionEnum::tryFrom($entry->action);

        if ($action === null) {
            return [['text' => $entry->action, 'kind' => 'text', 'url' => null]];
        }

        $values = $entry->parameters ?? [];
        $markers = [];

        foreach (array_keys($values) as $key) {
            $markers[$key] = "\u{E000}{$key}\u{E001}";
        }

        $pieces = preg_split('/\x{E000}(\w+)\x{E001}/u', __($action->description(), $markers), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $parts = [];

        foreach ($pieces as $index => $piece) {
            if ($index % 2 === 0) {
                if ($piece !== '') {
                    $parts[] = ['text' => $piece, 'kind' => 'text', 'url' => null];
                }

                continue;
            }

            $value = (string) ($values[$piece] ?? '');

            $parts[] = match (true) {
                $piece === 'flag' => ['text' => $value, 'kind' => 'code', 'url' => null],
                $piece === 'name' && $action->namesTheAccountHolder() && $entry->account !== null => [
                    'text' => $value,
                    'kind' => 'link',
                    'url' => route('admin.accounts.show', ['account' => $entry->account_id]),
                ],
                default => ['text' => $value, 'kind' => 'text', 'url' => null],
            };
        }

        return $parts;
    }

    public function icon(AdministratorLog $entry): string
    {
        return AdministratorActionEnum::tryFrom($entry->action)?->icon() ?? 'timeline';
    }

    public function authorIcon(AdministratorLog $entry): string
    {
        return $entry->wasPerformedByTheConsole() ? 'device' : 'profile';
    }

    public function moment(AdministratorLog $entry): CarbonImmutable
    {
        return $entry->created_at->setTimezone($this->viewer->timezone);
    }

    public function time(AdministratorLog $entry): string
    {
        return $this->viewer->time_format->format($this->moment($entry));
    }

    public function ago(AdministratorLog $entry): string
    {
        return $entry->created_at->diffForHumans();
    }

    private function writeDate(CarbonImmutable $date): string
    {
        return $this->viewer->date_format?->format($date)
            ?? $date->locale(app()->getLocale())->translatedFormat((string) AvailableLocales::details(app()->getLocale())['date_format']);
    }

    /** @return Builder<AdministratorLog> */
    private function matching(): Builder
    {
        $entries = AdministratorLog::query();

        if ($this->administratorId !== null) {
            $entries->where('user_id', $this->administratorId);
        }

        if ($this->accountId !== null) {
            $entries->where('account_id', $this->accountId);
        }

        return $entries;
    }
}
