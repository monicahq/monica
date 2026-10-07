<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\TooManyRepetitions;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\LifeEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Add one repetition to a repeater: a second phone number, a third address.
 */
class CreateFieldValueRow
{
    private FieldValueRow $row;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity|LifeEvent $valuable,
        private readonly Field $field,
        private readonly bool $writesToTheJournal = true,
    ) {}

    public function execute(): FieldValueRow
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->valuable->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }

        if (! $this->field->is_repeatable) {
            throw new InvalidArgumentException('This field is not a repeater, so it holds no repetitions.');
        }

        $count = $this->countTheRepetitions();

        if ($this->field->repeat_max !== null && $count >= $this->field->repeat_max) {
            throw new TooManyRepetitions("This field allows at most {$this->field->repeat_max} repetitions.");
        }
    }

    private function create(): void
    {
        $this->row = FieldValueRow::query()->create([
            'account_id' => $this->user->account_id,
            'valuable_type' => $this->valuable->getMorphClass(),
            'valuable_id' => $this->valuable->id,
            'field_id' => $this->field->id,
            'position' => $this->countTheRepetitions() + 1,
        ]);
    }

    private function countTheRepetitions(): int
    {
        return FieldValueRow::query()
            ->where('valuable_type', $this->valuable->getMorphClass())
            ->where('valuable_id', $this->valuable->id)
            ->where('field_id', $this->field->id)
            ->count();
    }

    private function log(): void
    {
        if (! $this->writesToTheJournal) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValueRowCreation,
            vault: $this->valuable->vault,
            parameters: ['label' => $this->field->label, 'name' => $this->valuable->name],
            loggable: $this->row,
        )->onQueue('low');
    }
}
