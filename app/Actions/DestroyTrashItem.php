<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TrashItemKindEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\RecordSnapshot;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete one item of the vault's trash for good, with everything that went to the trash with it. The
 * files it held are let go of, and are swept up once nothing else points at them.
 */
class DestroyTrashItem
{
    private TrashItem $item;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly TrashItemKindEnum $kind,
        private readonly string $uuid,
        private readonly bool $writesToLog = true,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $this->item = new VaultTrash(vault: $this->vault)->find($this->kind, $this->uuid);
    }

    private function destroy(): void
    {
        DB::transaction(fn () => $this->erase($this->item->record));
    }

    private function erase(Model $record): void
    {
        if ($record instanceof Contact || $record instanceof Group) {
            foreach ([Relationship::class, GroupMember::class, Reminder::class, LifeEvent::class, GroupNote::class, GroupFile::class] as $dependent) {
                $dependent::query()->deletedWithOwner($record)->get()->each(fn (Model $item) => $this->erase($item));
            }
        }

        match (true) {
            $record instanceof Contact => $this->eraseRecord($record, 'related_contact_id'),
            $record instanceof Entity => $this->eraseRecord($record, 'related_entity_id'),
            $record instanceof Group => $this->eraseGroup($record),
            $record instanceof LifeEvent => $this->eraseLifeEvent($record),
            $record instanceof GroupFile => $this->eraseAttachment($record),
            default => $record->forceDelete(),
        };
    }

    private function eraseRecord(Contact|Entity $record, string $pointingColumn): void
    {
        $morphClass = $record->getMorphClass();

        Reminder::withTrashed()->where('remindable_type', $morphClass)->where('remindable_id', $record->id)->forceDelete();
        ShareLink::query()->where('shareable_type', $morphClass)->where('shareable_id', $record->id)->delete();
        RecordSnapshot::query()->where('snapshotable_type', $morphClass)->where('snapshotable_id', $record->id)->delete();

        $participations = LifeEventParticipant::query()->where('participant_type', $morphClass)->where('participant_id', $record->id);
        $lifeEventIds = $participations->clone()->pluck('life_event_id')->all();
        $participations->delete();

        foreach ($lifeEventIds as $lifeEventId) {
            LifeEvent::withTrashed()->whereKey($lifeEventId)->update([
                'participant_count' => LifeEventParticipant::query()->where('life_event_id', $lifeEventId)->count(),
            ]);
        }

        LifeEvent::onlyTrashed()
            ->whereKey($lifeEventIds)
            ->whereDoesntHave('participants', fn (Builder $participants): Builder => $participants->where('participant_type', new Contact()->getMorphClass()))
            ->get()
            ->each(fn (LifeEvent $lifeEvent) => $this->eraseLifeEvent($lifeEvent));

        $pointingAtIt = FieldValue::query()->where($pointingColumn, $record->id);
        $this->releaseTheChoicesOf($pointingAtIt->clone());
        $pointingAtIt->delete();

        $this->eraseValuesOf($record);

        if ($record->avatar_file_id !== null) {
            $this->letGoOf($record->avatar_file_id);
        }

        $record->forceDelete();
    }

    private function eraseGroup(Group $group): void
    {
        Reminder::withTrashed()->where('remindable_type', $group->getMorphClass())->where('remindable_id', $group->id)->forceDelete();

        GroupFile::withTrashed()->where('group_id', $group->id)->get()->each(fn (GroupFile $attachment) => $this->eraseAttachment($attachment));

        $group->forceDelete();
    }

    private function eraseLifeEvent(LifeEvent $lifeEvent): void
    {
        $this->eraseValuesOf($lifeEvent);

        $lifeEvent->forceDelete();
    }

    private function eraseAttachment(GroupFile $attachment): void
    {
        $fileId = $attachment->file_id;

        $attachment->forceDelete();

        $this->letGoOf($fileId);
    }

    private function eraseValuesOf(Contact|Entity|LifeEvent $record): void
    {
        $values = FieldValue::query()->where('valuable_type', $record->getMorphClass())->where('valuable_id', $record->id);

        $values->clone()->whereNotNull('value_file_id')->pluck('value_file_id')->each(fn (int $fileId) => $this->letGoOf($fileId));

        $this->releaseTheChoicesOf($values->clone());
        $values->delete();

        FieldValueRow::query()->where('valuable_type', $record->getMorphClass())->where('valuable_id', $record->id)->delete();
    }

    private function letGoOf(int $fileId): void
    {
        $file = File::query()->find($fileId);

        if ($file === null || $file->reference_count === 0) {
            return;
        }

        new DetachFile(user: $this->user, file: $file)->execute();
    }

    /** @param Builder<FieldValue> $values */
    private function releaseTheChoicesOf(Builder $values): void
    {
        $taken = $values
            ->get(['value_list_item_id', 'value_list_item_ids'])
            ->flatMap(fn (FieldValue $value): array => [$value->value_list_item_id, ...($value->value_list_item_ids ?? [])])
            ->filter()
            ->countBy();

        foreach ($taken as $itemId => $total) {
            ValueListItem::query()
                ->whereKey($itemId)
                ->where('usage_count', '>=', $total)
                ->decrement('usage_count', $total);
        }
    }

    private function log(): void
    {
        if (! $this->writesToLog) {
            return;
        }

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TrashItemDestruction,
            vault: $this->vault,
            parameters: ['name' => $this->item->title(), 'vault' => $this->vault->name],
        );
    }
}
