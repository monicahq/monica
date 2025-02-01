<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScoutHelper
{
    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @codeCoverageIgnore
     */
    public static function isActivated(): bool
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
    public static function isIndexed(): bool
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
     * Test if the driver supports full text indexes.
     *
     * @codeCoverageIgnore
     */
    public static function isFullTextIndex(): bool
    {
        return config('scout.full_text_index') && in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql']);
    }

    /**
     * Get id and basic elements of this model.
     *
     * @codeCoverageIgnore
     */
    public static function id(Model $model): array
    {
        if (config('scout.driver') === 'database') {
            return [];
        }

        $id = $model->getKey();

        if ($id !== null && $model->getKeyType() === 'string' || config('scout.driver') === 'typesense') {
            $id = (string) $id;
        }

        return [
            'id' => $id,
            'vault_id' => (string) $model->getAttribute('vault_id'),
            'created_at' => (int) optional($model->getAttribute(Model::CREATED_AT))->timestamp,
            'updated_at' => (int) optional($model->getAttribute(Model::UPDATED_AT))->timestamp,
        ];
    }
}
