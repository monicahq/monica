<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Helpers\PageSize;
use App\Models\Administrator;
use Illuminate\Pagination\LengthAwarePaginator;

class AdministratorsViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, Administrator> */
    public function administrators(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            Administrator::query()
                ->with(['user', 'grantedBy'])
                ->oldest(),
            $this->size,
        ));
    }

    public function count(): int
    {
        return once(fn (): int => Administrator::query()->count());
    }

    public function isTheLastOne(): bool
    {
        return $this->count() === 1;
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
