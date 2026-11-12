<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a possession tag and give it another vault colour. The new name may differ from its own only
 * by case, but not match another tag of the account ignoring case.
 */
class UpdatePossessionTag
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionTag $possessionTag,
        private string $name,
        private readonly VaultColourEnum $colour,
    ) {}

    public function execute(): PossessionTag
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->possessionTag;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->possessionTag->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession tag is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this tag.'),
            ]);
        }

        $taken = $this->user->account->possessionTags()
            ->whereKeyNot($this->possessionTag->id)
            ->pluck('name')
            ->contains(fn (string $name): bool => mb_strtolower($name) === mb_strtolower($this->name));

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => __('You already have a tag with this name.'),
            ]);
        }
    }

    private function update(): void
    {
        $this->possessionTag->name = $this->name;
        $this->possessionTag->colour = $this->colour->value;
        $this->possessionTag->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTagUpdate,
            parameters: ['name' => $this->possessionTag->name],
            loggable: $this->possessionTag,
        )->onQueue('low');
    }
}
