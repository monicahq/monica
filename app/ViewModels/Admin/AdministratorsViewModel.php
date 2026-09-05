<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\Administrator;
use Illuminate\Pagination\Paginator;

class AdministratorsViewModel
{
    private const int PER_PAGE = 25;

    /** @return Paginator<int, Administrator> */
    public function administrators(): Paginator
    {
        return once(fn (): Paginator => Administrator::query()
            ->with(['user', 'grantedBy'])
            ->oldest()
            ->simplePaginate(self::PER_PAGE));
    }

    public function count(): int
    {
        return once(fn (): int => Administrator::query()->count());
    }

    public function isTheLastOne(): bool
    {
        return $this->count() === 1;
    }
}
