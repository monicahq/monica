<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Correct a record of a type the account invented: its name and everything its template asks, on the
 * template version the record already uses, which the correction never changes.
 */
class UpdateEntity
{
    /**
     * @param  array<int, mixed>  $values  what was typed, keyed by field id, uploads included
     * @param  array<int, mixed>  $labels  the label picked beside a value, keyed by field id
     * @param  array<int, mixed>  $labelWordings  the wording typed when that label is the open one
     * @param  array<int, bool>  $yearIsUnknown
     * @param  array<int, bool>  $monthIsUnknown
     * @param  array<int, int>  $keptFieldIds  the fields to leave exactly as they are, such as those an API call left out
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Entity $entity,
        private string $name,
        private readonly array $values = [],
        private readonly array $labels = [],
        private readonly array $labelWordings = [],
        private readonly array $yearIsUnknown = [],
        private readonly array $monthIsUnknown = [],
        private readonly array $keptFieldIds = [],
    ) {}

    public function execute(): Entity
    {
        $this->name = (string) TextSanitizer::nullablePlainText($this->name);

        $this->validate();

        DB::transaction(function (): void {
            $this->entity->update(['name' => $this->name]);

            foreach ($this->entity->structureVersion->assembly as $section) {
                new SetFieldValues(
                    user: $this->user,
                    record: $this->entity,
                    section: $section,
                    values: $this->values,
                    labels: $this->labels,
                    labelWordings: $this->labelWordings,
                    yearIsUnknown: $this->yearIsUnknown,
                    monthIsUnknown: $this->monthIsUnknown,
                    keptFieldIds: $this->keptFieldIds,
                )->execute();
            }
        });

        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new EntityNeedsAName(__('Write a name for this record.'));
        }

        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->entity->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityUpdate,
            vault: $this->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        );
    }
}
