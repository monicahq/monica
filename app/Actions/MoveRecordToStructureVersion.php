<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Reminder;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Put a record on another structure.
 *
 * A section copied into a new version gets new fields, so a field nobody touched has another id
 * there. Before anything is set aside, every value, repeated row and reminder of the record follows
 * its field to the field of the new version with the same section, key and type. Failing that, it
 * follows the one field with the same key and type elsewhere in the new version, a field inside a
 * repeater only under the repeater its own followed to. Only what has no such field, more than one,
 * a field another value already fills, or another held field wanting the same one, is set aside,
 * and a field put back later brings its values back the same way.
 *
 * The snapshot and the move are one transaction, so a deadlock leaves neither behind and the move is
 * run again, from the record as the database holds it.
 */
class MoveRecordToStructureVersion
{
    private StructureVersion $from;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
        private readonly StructureVersion $structureVersion,
    ) {}

    public function execute(): Contact|Entity
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->record->refresh();

            new CreateRecordSnapshot(
                user: $this->user,
                record: $this->record,
                reason: SnapshotReasonEnum::TemplateChange,
            )->execute();

            $this->move();
        }, attempts: 3);

        $this->record->searchable();
        $this->log();

        return $this->record;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        if ($this->structureVersion->status !== VersionStatusEnum::Published) {
            throw new InvalidArgumentException('A record can only be put on a published version.');
        }

        $describes = $this->record instanceof Entity
            ? StructureAppliesToEnum::Entity
            : StructureAppliesToEnum::Contact;

        if ($this->structureVersion->structure->applies_to !== $describes) {
            throw new InvalidArgumentException('This template does not describe this kind of record.');
        }

        if ($this->record instanceof Entity && $this->structureVersion->structure->entity_type_id !== $this->record->entity_type_id) {
            throw new InvalidArgumentException('This template describes another kind of record.');
        }
    }

    private function move(): void
    {
        $this->from = $this->record->structureVersion;

        if ($this->from->is($this->structureVersion)) {
            return;
        }

        $this->record->structure_version_id = $this->structureVersion->id;
        $this->record->save();

        StructureVersion::query()
            ->whereKey($this->from->id)
            ->where('record_count', '>', 0)
            ->decrement('record_count');

        StructureVersion::query()->whereKey($this->structureVersion->id)->increment('record_count');

        $this->followTheFieldsIntoTheNewVersion();
        $this->setAsideWhatTheNewStructureDoesNotCarry();
        $this->bringBackWhatItCarriesAgain();
    }

    /** @return list<int> */
    private function fieldsCarriedNow(): array
    {
        return $this->structureVersion->assembly
            ->load('sectionVersion.fields')
            ->flatMap(fn ($row): array => $row->sectionVersion->fields->pluck('id')->all())
            ->all();
    }

    private function followTheFieldsIntoTheNewVersion(): void
    {
        $values = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id);

        $rows = FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id);

        $carried = $this->fieldsCarriedNow();

        $heldIds = $values->clone()->pluck('field_id')
            ->merge($rows->clone()->pluck('field_id'))
            ->unique()
            ->diff($carried);

        if ($heldIds->isEmpty()) {
            return;
        }

        $carriedFields = Field::query()
            ->whereIn('id', $carried)
            ->with('sectionVersion')
            ->get();

        $inTheSameSection = $carriedFields->keyBy(fn (Field $field): string => "{$field->sectionVersion->section_id}|{$field->key}|{$field->type->value}");
        $anywhere = $carriedFields->groupBy(fn (Field $field): string => "{$field->parent_field_id}|{$field->key}|{$field->type->value}");

        $heldFields = Field::query()
            ->whereIn('id', $heldIds)
            ->with('sectionVersion')
            ->get()
            ->sortBy(fn (Field $held): bool => $held->parent_field_id !== null);

        $sameSectionKey = fn (Field $held): string => "{$held->sectionVersion->section_id}|{$held->key}|{$held->type->value}";

        $wanted = $heldFields
            ->reject(fn (Field $held): bool => $inTheSameSection->has($sameSectionKey($held)))
            ->countBy(fn (Field $held): string => "{$held->parent_field_id}|{$held->key}|{$held->type->value}");

        /** @var array<int, true> $taken */
        $taken = $values->clone()->whereIn('field_id', $carried)->pluck('field_id')
            ->merge($rows->clone()->whereIn('field_id', $carried)->pluck('field_id'))
            ->merge($heldFields->map(fn (Field $held): ?int => $inTheSameSection->get($sameSectionKey($held))?->id)->filter())
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();

        /** @var array<int, Field> $followed */
        $followed = [];

        $heldFields->each(function (Field $held) use ($inTheSameSection, $anywhere, $sameSectionKey, $wanted, &$taken, &$followed, $values, $rows): void {
            $counterpart = $inTheSameSection->get($sameSectionKey($held));

            if (! $counterpart instanceof Field) {
                if ($wanted->get("{$held->parent_field_id}|{$held->key}|{$held->type->value}") > 1) {
                    return;
                }

                $parent = $held->parent_field_id === null ? null : ($followed[$held->parent_field_id] ?? null);

                if ($held->parent_field_id !== null && $parent === null) {
                    return;
                }

                $candidates = $anywhere->get("{$parent?->id}|{$held->key}|{$held->type->value}");

                if ($candidates === null || $candidates->count() !== 1) {
                    return;
                }

                $counterpart = $candidates->first();

                if (isset($taken[$counterpart->id])) {
                    return;
                }

                $taken[$counterpart->id] = true;
            }

            $followed[$held->id] = $counterpart;

            Reminder::query()
                ->whereIn('field_value_id', $values->clone()->where('field_id', $held->id)->select('id'))
                ->where('field_id', $held->id)
                ->update(['field_id' => $counterpart->id]);

            $values->clone()->where('field_id', $held->id)->update(['field_id' => $counterpart->id]);
            $rows->clone()->where('field_id', $held->id)->update(['field_id' => $counterpart->id]);
        });
    }

    private function setAsideWhatTheNewStructureDoesNotCarry(): void
    {
        $carried = $this->fieldsCarriedNow();

        $leaving = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('is_detached', false)
            ->whereNotIn('field_id', $carried)
            ->get();

        $leaving
            ->filter(fn (FieldValue $value): bool => $value->file !== null)
            ->each(fn (FieldValue $value) => new DetachFile(user: $this->user, file: $value->file)->execute());

        FieldValue::query()
            ->whereIn('id', $leaving->modelKeys())
            ->update(['is_detached' => true, 'detached_at' => now()]);

        Reminder::query()
            ->whereIn('field_value_id', $leaving->modelKeys())
            ->active()
            ->get()
            ->each(fn (Reminder $rule) => new DeactivateReminders(
                user: $this->user,
                subject: $rule,
                reason: ReminderInactiveReasonEnum::FieldRemoved,
            )->execute());
    }

    private function bringBackWhatItCarriesAgain(): void
    {
        $carried = $this->fieldsCarriedNow();

        $returning = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('is_detached', true)
            ->whereIn('field_id', $carried)
            ->get();

        $returning
            ->filter(fn (FieldValue $value): bool => $value->file !== null)
            ->each(fn (FieldValue $value) => new AttachFile(user: $this->user, file: $value->file)->execute());

        FieldValue::query()
            ->whereIn('id', $returning->modelKeys())
            ->update(['is_detached' => false, 'detached_at' => null]);

        Reminder::query()
            ->whereIn('field_value_id', $returning->modelKeys())
            ->where('inactive_reason', ReminderInactiveReasonEnum::FieldRemoved)
            ->get()
            ->each(fn (Reminder $rule) => new ReactivateReminders(
                user: $this->user,
                subject: $rule,
                reason: ReminderInactiveReasonEnum::FieldRemoved,
            )->execute());
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RecordMovedToTemplateVersion,
            vault: $this->record->vault,
            parameters: [
                'name' => $this->record->name,
                'version' => $this->structureVersion->version_number,
            ],
            loggable: $this->record,
        );
    }
}
