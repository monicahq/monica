<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Add a column to a list of a vault's Knowledge, after the columns already there. Every item reads
 * it as blank until somebody writes in it.
 */
class CreateKnowledgeListColumn
{
    private KnowledgeListColumn $column;

    private string $cleanName;

    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
        private readonly string $name,
    ) {}

    public function execute(): KnowledgeListColumn
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->column;
    }

    private function validate(): void
    {
        if ($this->list->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This list is in another account.');
        }

        $this->cleanName = TextSanitizer::plainText($this->name);

        if ($this->cleanName === '') {
            throw ValidationException::withMessages(['name' => __('Give the column a name.')]);
        }

        if (mb_strlen($this->cleanName) > 255) {
            throw ValidationException::withMessages(['name' => __('Keep the name under 255 characters.')]);
        }
    }

    private function create(): void
    {
        $this->column = KnowledgeListColumn::query()->create([
            'account_id' => $this->list->account_id,
            'vault_id' => $this->list->vault_id,
            'knowledge_list_id' => $this->list->id,
            'name' => $this->cleanName,
            'position' => (int) $this->list->columns()->max('position') + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListColumnCreation,
            parameters: ['label' => $this->column->name, 'name' => $this->list->title],
            loggable: $this->column,
        )->onQueue('low');
    }
}
