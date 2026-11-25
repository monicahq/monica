<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\FieldCorrespondence;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
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
 * run again, from the record as the database holds it. The record's row is locked before anything
 * is decided, so a second request for the same move waits, then finds it done and writes nothing.
 */
class MoveRecordToStructureVersion
{
    private StructureVersion $from;

    private ?FieldCorrespondence $correspondence = null;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
        private readonly StructureVersion $structureVersion,
        private readonly bool $writesToLog = true,
    ) {}

    public function execute(): Contact|Entity
    {
        $this->validate();

        $hasMoved = DB::transaction(function (): bool {
            $currentVersionId = $this->record->newQueryWithoutScopes()
                ->whereKey($this->record->id)
                ->lockForUpdate()
                ->value('structure_version_id');

            if ((int) $currentVersionId === $this->structureVersion->id) {
                return false;
            }

            $this->record->refresh();

            new CreateRecordSnapshot(
                user: $this->user,
                record: $this->record,
                reason: SnapshotReasonEnum::TemplateChange,
                writesToLog: $this->writesToLog,
            )->execute();

            $this->move();

            return true;
        }, attempts: 3);

        if (! $hasMoved) {
            return $this->record->refresh();
        }

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
        return $this->correspondence()->carriedFieldIds();
    }

    private function correspondence(): FieldCorrespondence
    {
        return $this->correspondence ??= new FieldCorrespondence($this->structureVersion);
    }

    private function followTheFieldsIntoTheNewVersion(): void
    {
        $values = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id);

        $rows = FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id);

        foreach ($this->correspondence()->counterpartsFor($this->record) as $heldId => $counterpart) {
            Reminder::query()
                ->whereIn('field_value_id', $values->clone()->where('field_id', $heldId)->select('id'))
                ->where('field_id', $heldId)
                ->update(['field_id' => $counterpart->id]);

            $values->clone()->where('field_id', $heldId)->update(['field_id' => $counterpart->id]);
            $rows->clone()->where('field_id', $heldId)->update(['field_id' => $counterpart->id]);
        }
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
                writesToLog: $this->writesToLog,
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
                writesToLog: $this->writesToLog,
            )->execute());
    }

    private function log(): void
    {
        if (! $this->writesToLog) {
            return;
        }

        if ($this->from->structure_id !== $this->structureVersion->structure_id) {
            LogUserAction::dispatch(
                account: $this->user->account,
                user: $this->user,
                action: UserActionEnum::RecordMovedToTemplate,
                vault: $this->record->vault,
                parameters: [
                    'name' => $this->record->name,
                    'template' => $this->structureVersion->structure->owner->name,
                ],
                loggable: $this->record,
            );

            return;
        }

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
