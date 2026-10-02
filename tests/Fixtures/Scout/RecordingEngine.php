<?php

declare(strict_types=1);

namespace Tests\Fixtures\Scout;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Engines\NullEngine;
use Override;

/**
 * A search engine that searches nothing and remembers every record it was sent or told to forget,
 * and every model whose index it was told to empty, so a test can say which records a change
 * reached without running a real engine.
 */
class RecordingEngine extends NullEngine
{
    /** @var list<Model> */
    public array $sent = [];

    /** @var list<Model> */
    public array $removed = [];

    /** @var list<class-string<Model>> */
    public array $flushed = [];

    /** @param  Collection<int, Model>  $models */
    #[Override]
    public function update($models): void
    {
        foreach ($models as $model) {
            $this->sent[] = $model;
        }
    }

    /** @param  Collection<int, Model>  $models */
    #[Override]
    public function delete($models): void
    {
        foreach ($models as $model) {
            $this->removed[] = $model;
        }
    }

    /** @param  Model  $model */
    #[Override]
    public function flush($model): void
    {
        $this->flushed[] = $model::class;
    }

    public function wasSent(Model $model): bool
    {
        return $this->holds($this->sent, $model);
    }

    public function wasRemoved(Model $model): bool
    {
        return $this->holds($this->removed, $model);
    }

    public function forget(): void
    {
        $this->sent = [];
        $this->removed = [];
        $this->flushed = [];
    }

    /** @param  list<Model>  $models */
    private function holds(array $models, Model $model): bool
    {
        foreach ($models as $candidate) {
            if ($candidate->is($model)) {
                return true;
            }
        }

        return false;
    }
}
