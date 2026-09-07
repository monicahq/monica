<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a kind of link inside its category, so the ones somebody writes down every week sit at the
 * top of the picker.
 */
class UpdateRelationshipTypePosition
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipType $relationshipType,
        private readonly int $position,
    ) {}

    public function execute(): RelationshipType
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->relationshipType;
    }

    private function validate(): void
    {
        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }
    }

    private function update(): void
    {
        $types = RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where('relationship_category_id', $this->relationshipType->relationship_category_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (RelationshipType $type): bool => $type->is($this->relationshipType))
            ->values();

        $types->splice(max(0, $this->position - 1), 0, [$this->relationshipType]);

        DB::transaction(function () use ($types): void {
            $types->each(function (RelationshipType $type, int $index): void {
                $type->position = $index + 1;
                $type->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypePositionUpdate,
            parameters: ['name' => $this->relationshipType->name],
            loggable: $this->relationshipType,
        )->onQueue('low');
    }
}
