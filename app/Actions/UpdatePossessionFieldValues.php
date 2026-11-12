<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\PossessionFieldValues;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Write what a possession holds for some fields of its type. Only the fields named change, and an
 * empty value removes what the field held. Every value is checked against its field first, and
 * nothing is written unless all of them fit.
 */
class UpdatePossessionFieldValues
{
    /** @var Collection<string, PossessionField> */
    private Collection $fields;

    /** @param  array<string, mixed>  $values  keyed by the uuid of the field */
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly array $values,
    ) {}

    public function execute(): Possession
    {
        $this->validate();
        $this->write();
        $this->log();

        return $this->possession;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        $this->fields = PossessionFieldValues::fields(possessionTypeId: $this->possession->possession_type_id, values: $this->values);
    }

    private function write(): void
    {
        DB::transaction(fn () => PossessionFieldValues::write(possession: $this->possession, fields: $this->fields, values: $this->values));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionUpdate,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
