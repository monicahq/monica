<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RepetitionOrderEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Change what a field asks for and how it asks: its label, its help text, what it insists on.
 */
class UpdateField
{
    public function __construct(
        private readonly User $user,
        private readonly Field $field,
        private string $label,
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
    ) {}

    public function execute(): Field
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->field;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::plainText($this->label);
        $this->helpText = TextSanitizer::nullablePlainText($this->helpText);
        $this->placeholder = TextSanitizer::nullablePlainText($this->placeholder);
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }

        if ($this->valueList !== null && $this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        Field::refuseKindsItCannotTake($this->field->type, $this->options);

        if ($this->field->type->usesValueList() && $this->valueList === null) {
            throw new InvalidArgumentException('A field that offers choices has to say which list they come from.');
        }
    }

    private function update(): void
    {
        $this->field->label = $this->label;
        $this->field->help_text = $this->helpText;
        $this->field->placeholder = $this->placeholder;
        $this->field->is_required = $this->isRequired;
        $this->field->is_sensitive = $this->isSensitive;
        $this->field->is_highlighted = $this->isHighlighted;
        $this->field->is_unique = $this->isUnique;
        $this->field->is_identifying = $this->isIdentifying;
        $this->field->value_list_id = $this->field->type->usesValueList() || $this->field->type->usesALabelList() ? $this->valueList?->id : null;
        $this->field->default_value = $this->defaultValue;
        $this->field->options = $this->options;
        $this->field->validation = $this->validation;
        $this->field->repeat_min = $this->field->type->isRepeater() ? $this->repeatMin : null;
        $this->field->repeat_max = $this->field->type->isRepeater() ? $this->repeatMax : null;
        $this->field->repetition_order = $this->field->type->isRepeater() ? $this->repetitionOrder : null;
        $this->field->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldUpdate,
            parameters: [
                'label' => $this->field->label,
                'name' => $this->field->sectionVersion->section->name,
            ],
            loggable: $this->field,
        );
    }
}
