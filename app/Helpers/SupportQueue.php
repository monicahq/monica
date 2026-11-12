<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\SupportCategoryEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Builder;

/**
 * The administrators' support queue as a query: one view, the filters over it, and the count of
 * every view under the same filters. The admin screen, the API and the MCP server all read the
 * queue through it, so they always agree on what a filter means.
 */
class SupportQueue
{
    public const string ALL = 'all';

    public readonly string $view;

    public readonly string $search;

    public readonly string $category;

    public readonly string $user;

    public readonly string $age;

    public function __construct(
        string $view = '',
        string $search = '',
        string $category = '',
        string $user = '',
        string $age = '',
    ) {
        $this->view = in_array($view, [...SupportRequestStatusEnum::values(), self::ALL], true) ? $view : SupportRequestStatusEnum::ToHandle->value;
        $this->search = mb_trim($search);
        $this->category = in_array($category, SupportCategoryEnum::values(), true) ? $category : '';
        $this->user = ctype_digit($user) ? $user : '';
        $this->age = in_array($age, ['day', 'week', 'old'], true) ? $age : '';
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->user !== '' || $this->age !== '';
    }

    /** @return array<string, string> */
    public function parameters(): array
    {
        return array_filter([
            'view' => $this->view,
            'q' => $this->search,
            'category' => $this->category,
            'user' => $this->user,
            'age' => $this->age,
        ], fn (string $value): bool => $value !== '');
    }

    /** @return Builder<SupportRequest> */
    public function query(): Builder
    {
        return $this->filtered()
            ->when($this->view !== self::ALL, fn (Builder $query): Builder => $query->where('status', $this->view))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $counts = [];

        foreach (SupportRequestStatusEnum::cases() as $status) {
            $counts[$status->value] = $this->filtered()->where('status', $status->value)->count();
        }

        $counts[self::ALL] = $this->filtered()->count();

        return $counts;
    }

    /** @return Builder<SupportRequest> */
    private function filtered(): Builder
    {
        $search = $this->search;

        return SupportRequest::query()
            ->when($this->category !== '', fn (Builder $query): Builder => $query->where('category', $this->category))
            ->when($this->user !== '', fn (Builder $query): Builder => $query->where('user_id', (int) $this->user))
            ->when($this->age === 'day', fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDay()))
            ->when($this->age === 'week', fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->subDays(7), now()->subDay()]))
            ->when($this->age === 'old', fn (Builder $query): Builder => $query->where('created_at', '<', now()->subDays(7)))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                $number = (int) preg_replace('/\D/', '', $search);

                $query->whereLike('subject', "%{$search}%")
                    ->orWhereHas('messages', fn (Builder $messages): Builder => $messages->whereLike('body', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $users): Builder => $users
                        ->whereLike('first_name', "%{$search}%")
                        ->orWhereLike('last_name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%"));

                if ($number > 0) {
                    $query->orWhere('number', $number);
                }
            }));
    }
}
