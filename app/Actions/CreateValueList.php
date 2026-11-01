<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;

/**
 * Define a new set of choices for the fields of the account to draw from.
 *
 * A list made for one field is created unshared. It holds the choices somebody
 * typed into that field, is listed on no screen and is offered in no picker, so
 * it never reaches the account's vocabulary and produces no log entry.
 */
class CreateValueList
{
    private ValueList $valueList;

    public function __construct(
        private readonly User $user,
        private string $name,
        private ?string $description = null,
        private readonly bool $allowsUserValues = true,
        private readonly bool $isShared = true,
    ) {}

    public function execute(): ValueList
    {
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->valueList;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function create(): void
    {
        $positions = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->valueList = ValueList::query()->create([
            'account_id' => $this->user->account_id,
            'key' => null,
            'name' => $this->name,
            'name_translation_key' => null,
            'description' => $this->description,
            'is_system' => false,
            'allows_user_values' => $this->allowsUserValues,
            'is_shared' => $this->isShared,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    private function log(): void
    {
        if (! $this->isShared) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListCreation,
            parameters: ['name' => $this->valueList->name],
            loggable: $this->valueList,
        );
    }
}
