<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\SnapshotCannotBeRestored;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Put a record back the way it was at a moment.
 */
class RestoreRecordSnapshot
{
    private Contact|Entity $record;

    private TemplateVersion $version;

    /** @var array<int, int> */
    private array $rowMap = [];

    public function __construct(
        private readonly User $user,
        private readonly RecordSnapshot $snapshot,
    ) {}

    public function execute(): Contact|Entity
    {
        $this->validate();
        $this->refuseWhenTheStructureIsGone();

        new CreateRecordSnapshot(
            user: $this->user,
            record: $this->record,
            reason: SnapshotReasonEnum::Restore,
        )->execute();

        $this->restore();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->snapshot->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This snapshot is in another account.');
        }

        $record = $this->snapshot->snapshotable;

        if (! $record instanceof Contact && ! $record instanceof Entity) {
            throw new SnapshotCannotBeRestored('The record this snapshot was taken of is gone.');
        }

        $this->record = $record;
    }

    private function refuseWhenTheStructureIsGone(): void
    {
        $version = TemplateVersion::query()->find($this->snapshot->template_version_id);

        if ($version === null) {
            throw new SnapshotCannotBeRestored('The structure this snapshot was taken on no longer exists.');
        }

        $this->version = $version;

        $wanted = collect($this->snapshot->payload['values'] ?? [])
            ->pluck('field_id')
            ->merge(collect($this->snapshot->payload['rows'] ?? [])->pluck('field_id'))
            ->filter()
            ->unique();

        $found = Field::query()->whereIn('id', $wanted->all())->count();

        if ($found !== $wanted->count()) {
            throw new SnapshotCannotBeRestored('A field this snapshot wrote into no longer exists.');
        }
    }

    private function restore(): void
    {
        DB::transaction(function (): void {
            $this->clearWhatIsThereNow();
            $this->putTheRecordBack();
            $this->putTheRepetitionsBack();
            $this->putTheValuesBack();

            $this->snapshot->restored_at = now();
            $this->snapshot->save();
        });
    }

    private function clearWhatIsThereNow(): void
    {
        $values = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->with('file')
            ->get();

        $values->each(function (FieldValue $value): void {
            if ($value->file !== null) {
                new DetachFile(user: $this->user, file: $value->file)->execute();
            }
        });

        $this->moveTheChoiceCounts($values->pluck('value_list_item_id'), $values->pluck('value_list_item_ids'), '-');

        FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->delete();

        FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->delete();
    }

    private function putTheRecordBack(): void
    {
        $payload = $this->snapshot->payload['record'] ?? [];
        $wasOn = $this->record->template_version_id;

        $columns = $this->record instanceof Entity
            ? ['name', 'avatar_file_id', 'avatar_colour', 'is_archived']
            : [
                'prefix', 'first_name', 'middle_name', 'last_name', 'suffix', 'maiden_name',
                'nickname', 'name_order', 'avatar_file_id', 'avatar_colour', 'is_archived',
                'is_favourite',
            ];

        foreach ($columns as $column) {
            if (array_key_exists($column, $payload)) {
                $this->record->$column = $payload[$column];
            }
        }

        $this->record->template_version_id = $this->version->id;
        $this->record->save();

        if ($wasOn === $this->version->id) {
            return;
        }

        TemplateVersion::query()->whereKey($wasOn)->where('record_count', '>', 0)->decrement('record_count');
        TemplateVersion::query()->whereKey($this->version->id)->increment('record_count');
    }

    private function putTheRepetitionsBack(): void
    {
        foreach ($this->snapshot->payload['rows'] ?? [] as $row) {
            $created = FieldValueRow::query()->create([
                'account_id' => $this->record->account_id,
                'valuable_type' => $this->record->getMorphClass(),
                'valuable_id' => $this->record->id,
                'field_id' => $row['field_id'],
                'position' => $row['position'],
            ]);

            $this->rowMap[(int) $row['id']] = $created->id;
        }
    }

    private function putTheValuesBack(): void
    {
        $values = collect($this->snapshot->payload['values'] ?? []);

        foreach ($values as $value) {
            FieldValue::query()->create([
                'account_id' => $this->record->account_id,
                'vault_id' => $this->record->vault_id,
                'valuable_type' => $this->record->getMorphClass(),
                'valuable_id' => $this->record->id,
                'field_id' => $value['field_id'],
                'field_key' => $value['field_key'],
                'field_value_row_id' => $value['field_value_row_id'] === null
                    ? null
                    : ($this->rowMap[(int) $value['field_value_row_id']] ?? null),
                'value_string' => $value['value_string'],
                'value_text' => $value['value_text'],
                'value_number' => $value['value_number'],
                'value_boolean' => $value['value_boolean'],
                'value_date' => $value['value_date'],
                'value_datetime' => $value['value_datetime'],
                'value_json' => $value['value_json'],
                'value_list_item_id' => $value['value_list_item_id'],
                'value_list_item_ids' => $value['value_list_item_ids'],
                'value_file_id' => $value['value_file_id'],
                'related_contact_id' => $value['related_contact_id'],
                'related_entity_id' => $value['related_entity_id'],
                'year_is_unknown' => $value['year_is_unknown'],
                'month_is_unknown' => $value['month_is_unknown'],
                'is_detached' => $value['is_detached'],
                'detached_at' => $value['detached_at'],
            ]);
        }

        File::query()
            ->whereIn('id', $values->pluck('value_file_id')->filter()->all())
            ->get()
            ->each(fn (File $file) => new AttachFile(user: $this->user, file: $file)->execute());

        $this->moveTheChoiceCounts($values->pluck('value_list_item_id'), $values->pluck('value_list_item_ids'), '+');
    }

    /**
     * @param  Collection<int, int|string|null>  $single
     * @param  Collection<int, array<int, int|string>|null>  $several
     */
    private function moveTheChoiceCounts(Collection $single, Collection $several, string $direction): void
    {
        $ids = $single->merge($several->filter()->flatten())->filter()->map(fn (int|string $id): int => (int) $id);

        if ($ids->isEmpty()) {
            return;
        }

        foreach ($ids->countBy() as $id => $times) {
            $query = ValueListItem::query()->whereKey($id);

            if ($direction === '-') {
                $query->where('usage_count', '>=', $times)->decrement('usage_count', $times);

                continue;
            }

            $query->increment('usage_count', $times);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RecordSnapshotRestored,
            vault: $this->record->vault,
            parameters: ['name' => $this->record->name],
            loggable: $this->snapshot,
        )->onQueue('low');
    }
}
