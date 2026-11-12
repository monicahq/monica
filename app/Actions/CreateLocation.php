<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Add a place to the account: a name, and optionally the location it sits inside, an emoji and an
 * address. The parent has to be in the same account.
 */
class CreateLocation
{
    private Location $location;

    public function __construct(
        private readonly User $user,
        private string $name,
        private readonly ?Location $parent = null,
        private ?string $emoji = null,
        private ?string $address = null,
    ) {}

    public function execute(): Location
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->location;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->emoji = TextSanitizer::nullablePlainText($this->emoji);
        $this->address = TextSanitizer::nullablePlainText($this->address);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this location.'),
            ]);
        }

        if ($this->parent instanceof Location && $this->parent->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }
    }

    private function create(): void
    {
        $this->location = Location::query()->create([
            'account_id' => $this->user->account_id,
            'parent_id' => $this->parent?->id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'address' => $this->address,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LocationCreation,
            parameters: ['name' => $this->location->name],
            loggable: $this->location,
        )->onQueue('low');
    }
}
