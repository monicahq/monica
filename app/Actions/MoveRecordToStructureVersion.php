<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SnapshotReasonEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Put a record on another structure.
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

        new CreateRecordSnapshot(
            user: $this->user,
            record: $this->record,
            reason: SnapshotReasonEnum::TemplateChange,
        )->execute();

        $this->move();
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

        DB::transaction(function (): void {
            $this->record->structure_version_id = $this->structureVersion->id;
            $this->record->save();

            StructureVersion::query()
                ->whereKey($this->from->id)
                ->where('record_count', '>', 0)
                ->decrement('record_count');

            StructureVersion::query()->whereKey($this->structureVersion->id)->increment('record_count');

            $this->setAsideWhatTheNewStructureDoesNotCarry();
            $this->bringBackWhatItCarriesAgain();
        });
    }

    /** @return list<int> */
    private function fieldsCarriedNow(): array
    {
        return $this->structureVersion->assembly
            ->load('sectionVersion.fields')
            ->flatMap(fn ($row): array => $row->sectionVersion->fields->pluck('id')->all())
            ->all();
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
        )->onQueue('low');
    }
}
