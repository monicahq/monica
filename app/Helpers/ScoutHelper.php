<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get id and basic elements of this model.
     *
     * @codeCoverageIgnore
     */
    public static function id(Model $model): array
    {
        switch (config('scout.driver')) {
            case 'meilisearch':
                $id = (int) $model->id;
                break;
            case 'typesense':
                $id = (string) $model->id;
                break;
            default:
                $id = $model->id;
                break;
        }

        return [
            'id' => $id,
            'updated_at' => (int) $model->updated_at->timestamp,
            'created_at' => (int) $model->created_at->timestamp,
        ];
    }
}
