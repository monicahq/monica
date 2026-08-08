<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a kind of object, say what it is for, or change how it is drawn.
 *
 * The slug is deliberately left alone. It is the identifier URLs and the API
 * were given, and a rename is a change of wording rather than a change of
 * thing: "Animal" becoming "Pet" is the same type, and every link to it has to
 * keep working.
 *
 * Nothing here touches what a record of the type holds either. The structure
 * belongs to the template, and renaming the noun says nothing about it.
 */
class UpdateEntityType
{
    /** @var array<string, array<string, string|null>> */
    private array $changes = [];

    public function __construct(
        private readonly User $user,
        private readonly EntityType $entityType,
        private string $nameSingular,
        private string $namePlural,
        private ?string $description = null,
        private ?string $icon = null,
        private ?string $colour = null,
    ) {}

    public function execute(): EntityType
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->entityType;
    }

    private function sanitize(): void
    {
        $this->nameSingular = TextSanitizer::plainText($this->nameSingular);
        $this->namePlural = TextSanitizer::plainText($this->namePlural);
        $this->description = TextSanitizer::nullablePlainText($this->description);
        $this->icon = TextSanitizer::nullablePlainText($this->icon);
        $this->colour = mb_strtolower(TextSanitizer::nullablePlainText($this->colour) ?? EntityType::DEFAULT_COLOUR);
    }

    private function validate(): void
    {
        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }
    }

    private function update(): void
    {
        $before = [
            'name_singular' => $this->entityType->name_singular,
            'name_plural' => $this->entityType->name_plural,
            'description' => $this->entityType->description,
            'icon' => $this->entityType->icon,
            'colour' => $this->entityType->colour,
        ];

        $this->entityType->name_singular = $this->nameSingular;
        $this->entityType->name_plural = $this->namePlural;
        $this->entityType->description = $this->description;
        $this->entityType->icon = $this->icon;
        $this->entityType->colour = $this->colour;
        $this->entityType->save();

        foreach ($before as $field => $value) {
            if ($value !== $this->entityType->$field) {
                $this->changes[$field] = ['before' => $value, 'after' => $this->entityType->$field];
            }
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypeUpdate,
            parameters: ['name' => $this->entityType->name_plural],
            loggable: $this->entityType,
            changes: $this->changes,
        )->onQueue('low');
    }
}
