<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Change what a location is called, where it sits, its emoji or its address. Everything inside it moves
 * with it, which is why it can never be put inside itself or inside one of its own locations.
 */
class UpdateLocation
{
    public function __construct(
        private readonly User $user,
        private readonly Location $location,
        private string $name,
        private readonly ?Location $parent = null,
        private ?string $emoji = null,
        private ?string $address = null,
    ) {}

    public function execute(): Location
    {
        $this->sanitize();
        $this->validate();
        $this->update();
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
        if ($this->location->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this location.'),
            ]);
        }

        if (! $this->parent instanceof Location) {
            return;
        }

        if ($this->parent->account_id !== $this->location->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            $this->refuseCycle();

            $this->location->update([
                'parent_id' => $this->parent?->id,
                'name' => $this->name,
                'emoji' => $this->emoji,
                'address' => $this->address,
            ]);
        });
    }

    /**
     * The ancestry is read from locked rows rather than from loaded relations, so two moves that would
     * close a loop between them cannot both pass, and a loop already in the table is refused rather
     * than walked forever.
     */
    private function refuseCycle(): void
    {
        if (! $this->parent instanceof Location) {
            return;
        }

        $parents = Location::query()
            ->where('account_id', $this->location->account_id)
            ->lockForUpdate()
            ->pluck('parent_id', 'id');

        $ancestorId = $this->parent->id;
        $seen = [];

        while ($ancestorId !== null) {
            if ($ancestorId === $this->location->id) {
                throw new InvalidArgumentException(__('A location cannot sit inside itself, or inside a location it holds.'));
            }

            if (isset($seen[$ancestorId])) {
                throw new InvalidArgumentException(__('A location cannot sit inside itself, or inside a location it holds.'));
            }

            $seen[$ancestorId] = true;
            $ancestorId = $parents[$ancestorId] ?? null;
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LocationUpdate,
            parameters: ['name' => $this->location->name],
            loggable: $this->location,
        )->onQueue('low');
    }
}
