<?php

namespace App\Helpers;

class ScoutHelper
{
    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public static function activated()
    {
        switch (config('scout.driver')) {
            case 'algolia':
                return config('scout.algolia.id') !== '';
            case 'meilisearch':
                return config('scout.meilisearch.host') !== '';
            case 'database':
            case 'collection':
                return true;
            default:
                return false;
        }
    }
}
