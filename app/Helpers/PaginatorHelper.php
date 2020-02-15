<?php

namespace App\Helpers;

use App\Models\Contact\Gender;
use App\Models\Account\Account;
use Illuminate\Support\Collection;

class PaginatorHelper
{
    /**
     * Provide an array of all the data needed to display a pagination.
     *
     * @param mixed $eloquentCollection
     * @return array
     */
    public static function getData($eloquentCollection): array
    {
        return [
            'count' => $eloquentCollection->count(),
            'currentPage' => $eloquentCollection->currentPage(),
            'firstItem' => $eloquentCollection->firstItem(),
            'hasMorePages' => $eloquentCollection->hasMorePages(),
            'lastItem' => $eloquentCollection->lastItem(),
            'lastPage' => $eloquentCollection->lastPage(),
            'nextPageUrl' => $eloquentCollection->nextPageUrl(),
            'onFirstPage' => $eloquentCollection->onFirstPage(),
            'perPage' => $eloquentCollection->perPage(),
            'previousPageUrl' => $eloquentCollection->previousPageUrl(),
            'total' => $eloquentCollection->total(),
        ];
    }
}
