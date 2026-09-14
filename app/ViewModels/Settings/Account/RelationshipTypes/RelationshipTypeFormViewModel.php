<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\RelationshipTypes;

use App\Enums\RelationshipBehaviorEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;

class RelationshipTypeFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?RelationshipType $relationshipType = null,
        private readonly ?RelationshipCategory $wantedCategory = null,
    ) {}

    public function isEdit(): bool
    {
        return $this->relationshipType !== null;
    }

    public function isSystem(): bool
    {
        if ($this->relationshipType === null) {
            return false;
        }

        return $this->relationshipType->is_system;
    }

    public function title(): string
    {
        if ($this->relationshipType === null) {
            return __('Add a relationship type');
        }

        return $this->relationshipType->name;
    }

    public function description(): string
    {
        if ($this->relationshipType === null) {
            return __('Name the link, say whether both people are described with the same word, and write the words themselves.');
        }

        if ($this->relationshipType->is_system) {
            return __('You can change the words and where this type is listed. What the application does with it stays the same.');
        }

        return __('Your own type. Its name and category are yours to change, and neither changes how the application treats it.');
    }

    public function action(): string
    {
        if ($this->relationshipType === null) {
            return route('settings.relationshipTypes.create');
        }

        return route('settings.relationshipTypes.update', $this->relationshipType);
    }

    public function method(): string
    {
        return $this->relationshipType === null ? 'post' : 'put';
    }

    public function saveLabel(): string
    {
        return $this->relationshipType === null ? __('Add the type') : __('Save changes');
    }

    /**
     * @return array{name: string, categoryId: int, roleA: string, roleB: string, isSymmetric: bool, isActive: bool}
     */
    public function values(): array
    {
        $stored = $this->stored();

        return [
            'name' => (string) old('name', $stored['name']),
            'categoryId' => $this->categoryId($stored['categoryId']),
            'roleA' => (string) old('role_a', $stored['roleA']),
            'roleB' => (string) old('role_b', $stored['roleB']),
            'isSymmetric' => $this->isSymmetric($stored['isSymmetric']),
            'isActive' => $this->isActive($stored['isActive']),
        ];
    }

    /** @return array<int, array{value: int, label: string, selected: bool}> */
    public function categoryOptions(): array
    {
        $current = $this->values()['categoryId'];

        return RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (RelationshipCategory $category): array => [
                'value' => $category->id,
                'label' => $category->name,
                'selected' => $category->id === $current,
            ])
            ->values()
            ->all();
    }

    public function directionIsEditable(): bool
    {
        if ($this->relationshipType === null) {
            return true;
        }

        return ! $this->relationshipType->is_system && $this->relationshipType->relationshipCount() === 0;
    }

    public function lockedDirectionLabel(): string
    {
        if ($this->values()['isSymmetric']) {
            return __('The same from both sides');
        }

        return __('Different on each side');
    }

    public function lockedDirectionReason(): string
    {
        if ($this->isSystem()) {
            return __('A supplied type keeps its direction, so relationships written years ago still read the way they were written.');
        }

        return __('Relationships already use this type. You can change its wording, but not which side carries which role.');
    }

    public function behaviourTitle(): ?string
    {
        return match ($this->relationshipType?->system_behavior) {
            RelationshipBehaviorEnum::GenealogyParentage => __('Feeds the family tree'),
            RelationshipBehaviorEnum::Romantic => __('Feeds romantic history'),
            default => null,
        };
    }

    public function behaviourBody(): ?string
    {
        return match ($this->relationshipType?->system_behavior) {
            RelationshipBehaviorEnum::GenealogyParentage => __('The family tree is drawn from the relationships written with this type. Renaming it, moving it to another category or no longer offering it does not change that.'),
            RelationshipBehaviorEnum::Romantic => __('Relationships written with this type appear in romantic history, with their dates. Renaming it, moving it to another category or no longer offering it does not change that.'),
            default => null,
        };
    }

    public function availabilityHelp(): string
    {
        if ($this->values()['isActive']) {
            return __('Turn this off to retire the type. It leaves the picker, and the relationships that already use it are unchanged.');
        }

        return __('This type is not offered for new relationships. The ones that already use it still read as they did.');
    }

    public function canBeDeleted(): bool
    {
        if ($this->relationshipType === null) {
            return false;
        }

        return $this->user->can('delete', $this->relationshipType);
    }

    public function deleteBlockedReason(): string
    {
        if ($this->relationshipType === null) {
            return __('You can delete the type later, as long as no relationship uses it.');
        }

        if ($this->relationshipType->is_system) {
            return __('Supplied types cannot be deleted. If you never use this one, turn off “Offer this type for new relationships” above.');
        }

        return trans_choice(
            'This type is used by :count relationship, so it cannot be deleted. Turn off “Offer this type for new relationships” above to retire it instead.|This type is used by :count relationships, so it cannot be deleted. Turn off “Offer this type for new relationships” above to retire it instead.',
            $this->relationshipType->relationshipCount(),
        );
    }

    public function exampleOne(): string
    {
        return __('Alex');
    }

    public function exampleTwo(): string
    {
        return __('Sam');
    }

    public function sentenceTemplate(): string
    {
        return __(':one is :two’s :role.');
    }

    public function relationshipType(): ?RelationshipType
    {
        return $this->relationshipType;
    }

    public function user(): User
    {
        return $this->user;
    }

    /** @return array{name: string, categoryId: int|null, roleA: string, roleB: string, isSymmetric: bool, isActive: bool} */
    private function stored(): array
    {
        if ($this->relationshipType === null) {
            return [
                'name' => '',
                'categoryId' => $this->wantedCategoryId(),
                'roleA' => '',
                'roleB' => '',
                'isSymmetric' => true,
                'isActive' => true,
            ];
        }

        return [
            'name' => $this->relationshipType->name,
            'categoryId' => $this->relationshipType->relationship_category_id,
            'roleA' => $this->relationshipType->role_a,
            'roleB' => $this->relationshipType->role_b,
            'isSymmetric' => $this->relationshipType->is_symmetric,
            'isActive' => $this->relationshipType->is_active,
        ];
    }

    private function categoryId(?int $stored): int
    {
        $chosen = old('relationship_category_id');

        if ($chosen === null) {
            return $stored ?? 0;
        }

        return (int) $chosen;
    }

    private function wantedCategoryId(): ?int
    {
        if ($this->wantedCategory !== null) {
            return $this->wantedCategory->id;
        }

        return $this->firstCategoryId();
    }

    private function firstCategoryId(): ?int
    {
        return RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->value('id');
    }

    private function isSymmetric(bool $stored): bool
    {
        $chosen = old('direction');

        if ($chosen === null) {
            return $stored;
        }

        return $chosen === 'symmetric';
    }

    private function isActive(bool $stored): bool
    {
        if (session()->hasOldInput()) {
            return session()->hasOldInput('is_active');
        }

        return $stored;
    }
}
