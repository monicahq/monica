<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\UserActionEnum;
use App\Helpers\ContactListCatalogue;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ContactListColumn;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Choose the columns the list of people shows in a vault, in their order, replacing whatever the
 * vault showed before. A column showing a field no template carries any more may be kept as it was,
 * and may not be added anew.
 */
class UpdateContactListColumns
{
    /** @var list<array{source: ContactListColumnSourceEnum, sectionId: int|null, fieldKey: string|null, heading: string|null, isVisible: bool}> */
    private array $rows = [];

    /**
     * @param  list<array{source: string, section: string|null, field_key: string|null, heading: string|null, is_visible: bool}>  $columns
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly array $columns,
    ) {}

    public function execute(): Vault
    {
        $this->validateVault();
        $this->sanitize();
        $this->validate();
        $this->replace();
        $this->log();

        return $this->vault;
    }

    private function validateVault(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }
    }

    private function sanitize(): void
    {
        $sections = Section::query()
            ->where('account_id', $this->user->account_id)
            ->whereIn('uuid', array_filter(array_column($this->columns, 'section')))
            ->pluck('id', 'uuid');

        foreach ($this->columns as $column) {
            $source = ContactListColumnSourceEnum::tryFrom($column['source'])
                ?? throw new InvalidArgumentException("There is no column called {$column['source']}.");

            $sectionId = null;
            $fieldKey = null;

            if ($source === ContactListColumnSourceEnum::Field) {
                $sectionId = $sections[$column['section'] ?? ''] ?? throw new ModelNotFoundException('This section is in another account.');
                $fieldKey = $column['field_key'] ?? throw new InvalidArgumentException('A column showing a field has to name the field.');
            }

            $this->rows[] = [
                'source' => $source,
                'sectionId' => $sectionId,
                'fieldKey' => $fieldKey,
                'heading' => TextSanitizer::nullablePlainText($column['heading']),
                'isVisible' => $column['is_visible'],
            ];
        }
    }

    private function validate(): void
    {
        $identities = array_map(fn (array $row): string => "{$row['source']->value}:{$row['sectionId']}:{$row['fieldKey']}", $this->rows);

        if (count($identities) !== count(array_unique($identities))) {
            throw new InvalidArgumentException(__('The list cannot show the same column twice.'));
        }

        $catalogue = new ContactListCatalogue($this->vault->account);
        $kept = $this->vault->contactListColumns()
            ->where('source', ContactListColumnSourceEnum::Field)
            ->get()
            ->map(fn (ContactListColumn $column): string => "{$column->section_id}:{$column->field_key}")
            ->all();

        foreach ($this->rows as $row) {
            if ($row['source'] !== ContactListColumnSourceEnum::Field) {
                continue;
            }

            $isOffered = $catalogue->supplies((int) $row['sectionId'], (string) $row['fieldKey']);
            $isKept = in_array("{$row['sectionId']}:{$row['fieldKey']}", $kept, true);

            if (! $isOffered && ! $isKept) {
                throw new InvalidArgumentException(__('No template in this account carries that field, so the list has nothing to show in its column.'));
            }
        }
    }

    private function replace(): void
    {
        DB::transaction(function (): void {
            $this->vault->contactListColumns()->delete();

            foreach ($this->rows as $index => $row) {
                ContactListColumn::query()->create([
                    'account_id' => $this->vault->account_id,
                    'vault_id' => $this->vault->id,
                    'source' => $row['source'],
                    'section_id' => $row['sectionId'],
                    'field_key' => $row['fieldKey'],
                    'heading' => $row['heading'],
                    'position' => $index + 1,
                    'is_visible' => $row['isVisible'],
                ]);
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactListColumnsUpdate,
            vault: $this->vault,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        );
    }
}
