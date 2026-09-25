<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotNestARepeater;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Add a field to a section: "Street", "Shoe size", "Diet", "Birthday".
 */
class CreateField
{
    private Field $field;

    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
        private readonly FieldTypeEnum $type,
        private string $label,
        private ?string $key = null,
        private readonly ?Field $parent = null,
        private ?string $helpText = null,
        private ?string $placeholder = null,
        private readonly bool $isRequired = false,
        private readonly bool $isSensitive = false,
        private readonly bool $isHighlighted = false,
        private readonly bool $isUnique = false,
        private readonly bool $isIdentifying = false,
        private readonly ?ValueList $valueList = null,
        private readonly ?array $defaultValue = null,
        private readonly ?array $options = null,
        private readonly ?array $validation = null,
        private readonly ?int $repeatMin = null,
        private readonly ?int $repeatMax = null,
        private readonly ?RepetitionOrderEnum $repetitionOrder = null,
        private readonly ?RelationTargetEnum $relationTarget = null,
        private readonly ?EntityType $relationEntityType = null,
    ) {}

    public function execute(): Field
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->field;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::plainText($this->label);
        $this->key = TextSanitizer::nullablePlainText($this->key);
        $this->helpText = TextSanitizer::nullablePlainText($this->helpText);
        $this->placeholder = TextSanitizer::nullablePlainText($this->placeholder);
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }

        if ($this->valueList !== null && $this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        if ($this->relationEntityType !== null && $this->relationEntityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }

        Field::refuseKindsItCannotTake($this->type, $this->options);

        $this->validateParent();

        if ($this->type->usesValueList() && $this->valueList === null) {
            throw new InvalidArgumentException('A field that offers choices has to say which list they come from.');
        }

        if ($this->type->isRelation() && $this->relationTarget === RelationTargetEnum::Entity && $this->relationEntityType === null) {
            throw new InvalidArgumentException('A field pointing at an entity has to say which kind of entity.');
        }
    }

    private function validateParent(): void
    {
        if ($this->parent === null) {
            return;
        }

        if ($this->parent->section_version_id !== $this->sectionVersion->id) {
            throw new ModelNotFoundException('This repeater is in another section version.');
        }

        if (! $this->parent->type->isRepeater()) {
            throw new InvalidArgumentException('Only a repeater can hold other fields.');
        }

        if ($this->type->isRepeater()) {
            throw new CannotNestARepeater('A repeater cannot sit inside another repeater.');
        }
    }

    private function create(): void
    {
        $positions = Field::query()
            ->where('section_version_id', $this->sectionVersion->id)
            ->where('parent_field_id', $this->parent?->id)
            ->pluck('position');

        $this->field = Field::query()->create([
            'account_id' => $this->sectionVersion->account_id,
            'section_version_id' => $this->sectionVersion->id,
            'type' => $this->type,
            'key' => $this->key(),
            'label' => $this->label,
            'label_translation_key' => null,
            'help_text' => $this->helpText,
            'placeholder' => $this->placeholder,
            'position' => ($positions->max() ?? 0) + 1,
            'is_required' => $this->isRequired,
            'is_sensitive' => $this->isSensitive,
            'is_highlighted' => $this->isHighlighted,
            'is_unique' => $this->isUnique,
            'is_identifying' => $this->isIdentifying,
            'value_list_id' => $this->type->usesValueList() || $this->type->usesALabelList() ? $this->valueList?->id : null,
            'default_value' => $this->defaultValue,
            'options' => $this->options,
            'validation' => $this->validation,
            'is_repeatable' => $this->type->isRepeater(),
            'repeat_min' => $this->type->isRepeater() ? $this->repeatMin : null,
            'repeat_max' => $this->type->isRepeater() ? $this->repeatMax : null,
            'repetition_order' => $this->type->isRepeater() ? $this->repetitionOrder : null,
            'parent_field_id' => $this->parent?->id,
            'relation_target' => $this->type->isRelation() ? ($this->relationTarget ?? RelationTargetEnum::Contact) : null,
            'relation_entity_type_id' => $this->type->isRelation() ? $this->relationEntityType?->id : null,
        ]);
    }

    private function key(): string
    {
        return SlugBuilder::build(
            wanted: $this->key,
            derivedFrom: $this->label,
            maxLength: Field::KEY_LENGTH,
            whenEmpty: 'field',
            isTaken: fn (string $key): bool => Field::query()
                ->where('section_version_id', $this->sectionVersion->id)
                ->where('key', $key)
                ->exists(),
            separator: '_',
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldCreation,
            parameters: [
                'label' => $this->field->label,
                'name' => $this->sectionVersion->section->name,
            ],
            loggable: $this->field,
        )->onQueue('low');
    }
}
