<?php

namespace App\Helpers;

class ScoutHelper
{
    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @codeCoverageIgnore
     */
    public static function activated(): bool
    {
        switch (config('scout.driver')) {
            case 'algolia':
                return config('scout.algolia.id') !== '';
            case 'meilisearch':
                return config('scout.meilisearch.key') !== '';
            case 'typesense':
                return config('scout.typesense.client-settings.api_key') !== '';
            case 'database':
            case 'collection':
                return true;
            default:
                return false;
        }
    }

    /**
     * Test if the driver requires indexes.
     *
     * @codeCoverageIgnore
     */
    public static function indexed(): bool
    {
        switch (config('scout.driver')) {
            case 'algolia':
                return config('scout.algolia.id') !== '';
            case 'meilisearch':
                return config('scout.meilisearch.key') !== '';
            case 'typesense':
                return config('scout.typesense.client-settings.api_key') !== '';
            default:
                return false;
        }
    }
}
