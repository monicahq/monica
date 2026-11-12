<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Jobs\RebuildSearchIndex;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Start the rebuild of one search index: it is emptied, then every record of that kind is sent
 * again. The work itself is queued, since it walks every record of the instance.
 */
class StartSearchIndexRebuild
{
    public function __construct(
        private readonly string $kind,
        private readonly ?User $administrator = null,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->start();
        $this->log();
    }

    private function validate(): void
    {
        if (! array_key_exists($this->kind, CheckSearchEngine::INDEXES)) {
            throw new ModelNotFoundException("No search index holds {$this->kind}");
        }
    }

    private function start(): void
    {
        RebuildSearchIndex::dispatch(CheckSearchEngine::INDEXES[$this->kind])->onQueue('low');
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::SearchIndexRebuildStarted,
            parameters: ['index' => new (CheckSearchEngine::INDEXES[$this->kind])()->searchableAs()],
        )->onQueue('low');
    }
}
