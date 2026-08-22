<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Models\Administrator;
use Illuminate\Pagination\Paginator;

/**
 * Who runs this instance, where their capacity came from, and whether they can
 * currently use it.
 *
 * Somebody holding the capacity who cannot sign in is shown as exactly that
 * rather than as an administrator. It is the state that follows from granting it
 * to a deactivated person, or deactivating a person who holds it, and a list
 * that showed them as active would tell an operator they have cover they do not
 * have.
 */
class AdministratorsViewModel
{
    /**
     * How many a page holds. An instance has a handful, so this exists for the
     * shape of every other list in the area rather than for the volume.
     */
    private const int PER_PAGE = 25;

    /**
     * @return Paginator<int, Administrator>
     */
    public function administrators(): Paginator
    {
        return once(fn (): Paginator => Administrator::query()
            ->with(['user', 'grantedBy'])
            ->oldest()
            ->simplePaginate(self::PER_PAGE));
    }

    /**
     * How many hold it at all, which is what decides whether a withdrawal is
     * even offered. The last one cannot be withdrawn, and a control that always
     * fails is worse than no control.
     */
    public function count(): int
    {
        return once(fn (): int => Administrator::query()->count());
    }

    public function isTheLastOne(): bool
    {
        return $this->count() === 1;
    }
}
