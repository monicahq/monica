<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take a field out of a section.
 *
 * Only out of a draft. The published version that still carries it is untouched
 * and every record on it goes on showing what it always showed.
 *
 * Nothing already written is thrown away either. A field disappearing from a
 * newer version leaves its stored values behind, detached and restorable, which
 * is what makes a change to a structure something somebody can undo.
 *
 * What sits inside a repeater goes with the repeater.
 */
class DestroyField
{
    private Section $section;

    private string $label;

    public function __construct(
        private readonly User $user,
        private readonly Field $field,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->section = $this->field->sectionVersion->section;
        $this->label = $this->field->label;

        $this->field->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldDeletion,
            parameters: [
                'label' => $this->label,
                'name' => $this->section->name,
            ],
            loggable: $this->section,
        )->onQueue('low');
    }
}
