<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Build a new possession type from a file. What arrives is a copy and never touches a type already
 * there. A file with any problem creates nothing, and the refusal lists every problem.
 */
class ImportPossessionType
{
    private PossessionType $possessionType;

    /** @var array{name: string, colour: string, icon: string|null, groups: list<array{name: string, fields: list<array<string, mixed>>}>, fields: list<array<string, mixed>>} */
    private array $file;

    public function __construct(
        private readonly User $user,
        private readonly string $raw,
    ) {}

    /** @throws PossessionTypeFileIsNotReadable */
    public function execute(): PossessionType
    {
        $this->file = PossessionTypeFileReader::read($this->raw);

        $this->import();
        $this->log();

        return $this->possessionType;
    }

    private function import(): void
    {
        DB::transaction(function (): void {
            $this->possessionType = PossessionType::query()->create([
                'account_id' => $this->user->account_id,
                'name' => $this->file['name'],
                'colour' => $this->file['colour'],
                'icon' => $this->file['icon'],
            ]);

            foreach ($this->file['groups'] as $index => $group) {
                $created = PossessionFieldGroup::query()->create([
                    'account_id' => $this->user->account_id,
                    'possession_type_id' => $this->possessionType->id,
                    'name' => $group['name'],
                    'position' => $index + 1,
                ]);

                $this->createFields($group['fields'], $created);
            }

            $this->createFields($this->file['fields'], null);
        });
    }

    /** @param  list<array<string, mixed>>  $fields */
    private function createFields(array $fields, ?PossessionFieldGroup $group): void
    {
        foreach ($fields as $index => $field) {
            PossessionField::query()->create([
                'account_id' => $this->user->account_id,
                'possession_type_id' => $this->possessionType->id,
                'possession_field_group_id' => $group?->id,
                'name' => $field['name'],
                'kind' => $field['kind'],
                'options' => $field['options'],
                'position' => $index + 1,
            ]);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTypeImport,
            parameters: ['name' => $this->possessionType->name],
            loggable: $this->possessionType,
        )->onQueue('low');
    }
}
