<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Helpers\PossessionFieldOptions;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Add a field to a possession type, in one of its groups or standing alone, placed last among its
 * siblings. Without a name it is called "Untitled field", and without a kind it holds text.
 */
class CreatePossessionField
{
    private PossessionField $field;

    /** @param  list<string>|null  $options */
    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
        private readonly ?PossessionFieldGroup $group = null,
        private ?string $name = null,
        private readonly PossessionFieldKindEnum $kind = PossessionFieldKindEnum::Text,
        private ?array $options = null,
    ) {}

    public function execute(): PossessionField
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->field;
    }

    private function sanitize(): void
    {
        $this->name = $this->name === null
            ? __('Untitled field')
            : TextSanitizer::plainText($this->name);

        $this->options = PossessionFieldOptions::clean($this->kind, $this->options);
    }

    private function validate(): void
    {
        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }

        if ($this->group instanceof PossessionFieldGroup && $this->group->possession_type_id !== $this->possessionType->id) {
            throw new ModelNotFoundException('This group belongs to another possession type.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this field.'),
            ]);
        }

        PossessionFieldOptions::validate($this->options);
    }

    private function create(): void
    {
        $lastPosition = PossessionField::query()
            ->where('possession_type_id', $this->possessionType->id)
            ->where('possession_field_group_id', $this->group?->id)
            ->max('position');

        $this->field = PossessionField::query()->create([
            'account_id' => $this->possessionType->account_id,
            'possession_type_id' => $this->possessionType->id,
            'possession_field_group_id' => $this->group?->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'options' => $this->options,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldCreation,
            parameters: ['name' => $this->field->name, 'type' => $this->possessionType->name],
            loggable: $this->field,
        )->onQueue('low');
    }
}
