<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

class PaginatorHelper
{
    /**
     * Provide an array of all the data needed to display a pagination.
     */
    public static function getData(LengthAwarePaginator $paginator): array
    {
        return [
            'count' => $paginator->count(),
            'currentPage' => $paginator->currentPage(),
            'firstItem' => $paginator->firstItem(),
            'firstPageUrl' => $paginator->url(1),
            'hasMorePages' => $paginator->hasMorePages(),
            'lastItem' => $paginator->lastItem(),
            'lastPage' => $paginator->lastPage(),
            'lastPageUrl' => $paginator->url($paginator->lastPage()),
            'links' => $paginator->linkCollection()->toArray(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'onFirstPage' => $paginator->onFirstPage(),
            'path' => $paginator->path(),
            'perPage' => $paginator->perPage(),
            'previousPageUrl' => $paginator->previousPageUrl(),
            'total' => $paginator->total(),
        ];
    }
}
