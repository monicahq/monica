<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Add a word the account puts on its possessions. Its name is unique in the account ignoring case,
 * and without a colour it takes the first vault colour.
 */
class CreatePossessionTag
{
    private PossessionTag $possessionTag;

    public function __construct(
        private readonly User $user,
        private string $name,
        private readonly VaultColourEnum $colour = VaultColourEnum::Blue,
    ) {}

    public function execute(): PossessionTag
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->possessionTag;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this tag.'),
            ]);
        }

        $taken = $this->user->account->possessionTags()
            ->pluck('name')
            ->contains(fn (string $name): bool => mb_strtolower($name) === mb_strtolower($this->name));

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => __('You already have a tag with this name.'),
            ]);
        }
    }

    private function create(): void
    {
        $this->possessionTag = PossessionTag::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $this->name,
            'colour' => $this->colour->value,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTagCreation,
            parameters: ['name' => $this->possessionTag->name],
            loggable: $this->possessionTag,
        )->onQueue('low');
    }
}
