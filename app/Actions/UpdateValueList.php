<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a list, say what it is for, or decide whether somebody may add their own choices to it.
 */
class UpdateValueList
{
    /** @var array<string, array<string, string|bool|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
        private string $name,
        private ?string $description = null,
        private readonly bool $allowsUserValues = true,
    ) {}

    public function execute(): ValueList
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->valueList;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }
    }

    private function update(): void
    {
        $before = [
            'name' => $this->valueList->name,
            'description' => $this->valueList->description,
            'allows_user_values' => $this->valueList->allows_user_values,
        ];

        $this->valueList->name = $this->name;
        $this->valueList->name_translation_key = null;
        $this->valueList->description = $this->description;
        $this->valueList->allows_user_values = $this->allowsUserValues;
        $this->valueList->save();

        foreach ($before as $field => $value) {
            if ($value !== $this->valueList->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->valueList->$field];
            }
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListUpdate,
            parameters: ['name' => $this->valueList->name],
            loggable: $this->valueList,
            changes: $this->changes,
        )->onQueue('low');
    }
}
