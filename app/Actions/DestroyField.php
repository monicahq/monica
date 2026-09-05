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
