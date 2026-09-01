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
 *
 * Only to a draft. On a published section the model refuses it, because a field
 * appearing on a template version somebody published last month is exactly what publication
 * rules out.
 *
 * The key is settled here and never rebuilt. It is what an import, an export or
 * an API caller holds on to, so renaming "Shoe size" to "Size of shoe" leaves
 * it alone. It reads in underscores rather than dashes, since what reads it is
 * code rather than a person.
 *
 * Several settings on the row only mean something for certain types, and this
 * is where that is settled rather than left to whoever fills the form. A field
 * that is not a choice carries no value list, one that is not a relation points
 * at nothing, and only a date that comes round again can drive a reminder.
 * Storing a setting the type cannot honour would be storing a lie.
 *
 * A repeater holds ordinary fields and nothing else. Nesting one inside another
 * would compound the hardest accessibility problem in the builder for no stated
 * need.
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
        private readonly ?ValueList $valueList = null,
        private readonly ?array $defaultValue = null,
        private readonly ?array $options = null,
        private readonly ?array $validation = null,
        private readonly ?int $repeatMin = null,
        private readonly ?int $repeatMax = null,
        private readonly ?RepetitionOrderEnum $repetitionOrder = null,
        private readonly bool $isReminderSource = false,
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
            'value_list_id' => $this->type->usesValueList() ? $this->valueList?->id : null,
            'default_value' => $this->defaultValue,
            'options' => $this->options,
            'validation' => $this->validation,
            'is_repeatable' => $this->type->isRepeater(),
            'repeat_min' => $this->type->isRepeater() ? $this->repeatMin : null,
            'repeat_max' => $this->type->isRepeater() ? $this->repeatMax : null,
            'repetition_order' => $this->type->isRepeater() ? $this->repetitionOrder : null,
            'parent_field_id' => $this->parent?->id,
            'is_reminder_source' => $this->type->canDriveAReminder() && $this->isReminderSource,
            'relation_target' => $this->type->isRelation() ? ($this->relationTarget ?? RelationTargetEnum::Contact) : null,
            'relation_entity_type_id' => $this->type->isRelation() ? $this->relationEntityType?->id : null,
        ]);
    }

    /**
     * Settle the key, keeping it unique within the section. It is checked against
     * every field in the section rather than only against its siblings, since a
     * field moving in or out of a repeater must not have to change identity.
     */
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
