<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a column of a list of a vault's Knowledge. A blank name keeps the one it had.
 */
class UpdateKnowledgeListColumn
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeListColumn $column,
        private string $name,
    ) {}

    public function execute(): KnowledgeListColumn
    {
        $this->sanitize();
        $this->validate();

        if ($this->name === '') {
            return $this->column;
        }

        $this->update();
        $this->log();

        return $this->column;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->column->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This column is in another account.');
        }

        if (mb_strlen($this->name) > 255) {
            throw ValidationException::withMessages(['name' => __('Keep the name under 255 characters.')]);
        }
    }

    private function update(): void
    {
        $this->column->name = $this->name;
        $this->column->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListColumnUpdate,
            parameters: ['label' => $this->column->name, 'name' => $this->column->list->title],
            loggable: $this->column,
        )->onQueue('low');
    }
}
