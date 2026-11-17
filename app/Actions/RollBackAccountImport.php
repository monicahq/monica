<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportStatusEnum;
use App\Jobs\DetectDuplicateContacts;
use App\Models\Account;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Group;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\Possession;
use App\Models\RecordSnapshot;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\Notifications\AccountImportEnded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Remove for good everything the items of an import name, with the values, files and counts each
 * record raised, so the account is as it was before. What somebody made by hand meanwhile stays: a
 * type or a template they used, and a vault the import created that they added to.
 */
class RollBackAccountImport
{
    use RecountsLifeEvents;

    /** @var list<File> */
    private array $removedFiles = [];

    public function __construct(
        private readonly AccountImport $import,
    ) {}

    public function execute(): void
    {
        $hasRolledBack = DB::transaction(function (): bool {
            $import = AccountImport::query()->lockForUpdate()->findOrFail($this->import->id);

            if ($import->status === AccountImportStatusEnum::RolledBack) {
                return false;
            }

            $this->removeWhatWentIntoMe();
            $this->removeTheRecords();
            $this->removeTheOwnTypes();
            $this->removeTheCopiesOfStructures();
            $this->removeTheCategoriesItAdded();

            foreach (Vault::withTrashed()->whereIn('id', $import->created_vault_ids ?? [])->get() as $vault) {
                if (! $this->holdsSomethingMadeByHand($vault)) {
                    $this->removeTheVault($vault);
                }
            }

            $this->import->status = AccountImportStatusEnum::RolledBack;
            $this->import->ended_at = now();
            $this->import->save();

            return true;
        });

        if (! $hasRolledBack) {
            return;
        }

        foreach ($this->removedFiles as $file) {
            $file->disk()->delete($file->path);

            foreach ($file->variants ?? [] as $variant) {
                $file->disk()->delete($file->path.$variant['suffix']);
            }
        }

        Storage::disk($this->import->disk)->deleteDirectory($this->import->directory());

        $this->import->user->notify(new AccountImportEnded(
            imported: 0,
            leftOut: 0,
            rolledBack: true,
            url: route('settings.accountImports.show', $this->import),
        ));

        foreach (Vault::query()->whereKey($this->import->vaultIds())->get() as $vault) {
            DetectDuplicateContacts::dispatch($vault)->onQueue('low');
        }
    }

    private function removeWhatWentIntoMe(): void
    {
        $written = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('outcome', AccountImportOutcomeEnum::MergedIntoMe->value)
            ->get(['payload'])
            ->map(fn (AccountImportItem $item): array => $item->payload ?? []);

        $values = FieldValue::query()->whereIn('id', $written->flatMap(fn (array $added): array => (array) ($added['values'] ?? [])));
        $this->releaseTheChoicesOf($values->clone());
        $values->delete();

        FieldValueRow::query()->whereIn('id', $written->flatMap(fn (array $added): array => (array) ($added['rows'] ?? [])))->delete();

        $avatars = $written->pluck('avatar_file')->filter()->values();

        Contact::query()->whereIn('avatar_file_id', $avatars)->update(['avatar_file_id' => null]);
        $this->removeFromStorage(File::withTrashed()->whereIn('id', $avatars)->get());
    }

    private function removeTheRecords(): void
    {
        Reminder::withTrashed()->whereIn('id', $this->recordsOf(Reminder::class))->forceDelete();
        Relationship::withTrashed()->whereIn('id', $this->recordsOf(Relationship::class))->forceDelete();
        Reminder::withTrashed()->where('remindable_type', new Group()->getMorphClass())->whereIn('remindable_id', $this->recordsOf(Group::class))->forceDelete();
        Group::withTrashed()->whereIn('id', $this->recordsOf(Group::class))->forceDelete();

        $rows = $this->recordsOf(FieldValueRow::class);
        $this->releaseTheChoicesOf(FieldValue::query()->whereIn('field_value_row_id', $rows));
        FieldValue::query()->whereIn('field_value_row_id', $rows)->delete();
        FieldValueRow::query()->whereIn('id', $rows)->delete();

        $lifeEvents = $this->recordsOf(LifeEvent::class);
        $participants = LifeEventParticipant::query()
            ->whereIn('life_event_id', $lifeEvents)
            ->where('participant_type', new Contact()->getMorphClass())
            ->distinct()
            ->pluck('participant_id')
            ->all();

        $entities = $this->recordsOf(Entity::class);
        $contacts = $this->recordsOf(Contact::class);
        $pointingAtThem = FieldValue::query()->whereIn('related_contact_id', $contacts)->orWhereIn('related_entity_id', $entities);
        $this->releaseTheChoicesOf($pointingAtThem->clone());
        $pointingAtThem->delete();

        RecordSnapshot::query()->where('snapshotable_type', new Contact()->getMorphClass())->whereIn('snapshotable_id', $contacts)->delete();
        RecordSnapshot::query()->where('snapshotable_type', new Entity()->getMorphClass())->whereIn('snapshotable_id', $entities)->delete();
        $this->removeTheValuesOf(new LifeEvent()->getMorphClass(), $lifeEvents);
        $this->removeTheValuesOf(new Entity()->getMorphClass(), $entities);
        $this->removeTheValuesOf(new Contact()->getMorphClass(), $contacts);

        $this->giveTheCountsBack(LifeEvent::query()->whereIn('id', $lifeEvents), 'structure_version_id', StructureVersion::class, 'record_count');
        LifeEvent::withdrawFromSearch(LifeEvent::withTrashed()->whereIn('id', $lifeEvents));
        LifeEvent::withTrashed()->whereIn('id', $lifeEvents)->forceDelete();

        $this->giveTheCountsBack(Entity::query()->whereIn('id', $entities), 'structure_version_id', StructureVersion::class, 'record_count');
        $this->giveTheCountsBack(Entity::query()->whereIn('id', $entities), 'entity_type_id', EntityType::class, 'entity_count');
        Entity::withdrawFromSearch(Entity::withTrashed()->whereIn('id', $entities));
        Entity::withTrashed()->whereIn('id', $entities)->forceDelete();

        $files = File::withTrashed()
            ->whereIn('id', $this->recordsOf(File::class))
            ->orWhereIn('id', Contact::withTrashed()->whereIn('id', $contacts)->whereNotNull('avatar_file_id')->select('avatar_file_id'))
            ->get();
        $this->giveTheCountsBack($this->countedContacts()->whereIn('id', $contacts), 'structure_version_id', StructureVersion::class, 'record_count');
        $this->giveTheCountsBack($this->countedContacts()->whereIn('id', $contacts), 'vault_id', Vault::class, 'contact_count');
        Contact::withdrawFromSearch(Contact::withTrashed()->whereIn('id', $contacts));
        Contact::withTrashed()->whereIn('id', $contacts)->forceDelete();

        $this->recountLifeEventsOf($participants);

        $this->removeFromStorage($files);
    }

    private function holdsSomethingMadeByHand(Vault $vault): bool
    {
        if (Contact::withTrashed()->where('vault_id', $vault->id)->where('is_me', false)->exists()) {
            return true;
        }

        $softDeleted = [Entity::class, LifeEvent::class, Group::class, File::class, Reminder::class, Relationship::class];
        $removedForGood = [Possession::class, KnowledgeFolder::class, KnowledgeNote::class, KnowledgeList::class];

        return collect($softDeleted)->contains(fn (string $class): bool => $class::withTrashed()->where('vault_id', $vault->id)->exists())
            || collect($removedForGood)->contains(fn (string $class): bool => $class::query()->where('vault_id', $vault->id)->exists());
    }

    private function removeTheVault(Vault $vault): void
    {
        $this->removeFromStorage(File::withTrashed()->where('vault_id', $vault->id)->get());
        $this->releaseTheChoicesOf(FieldValue::query()->where('vault_id', $vault->id));
        FieldValue::query()->where('vault_id', $vault->id)->delete();

        $this->giveTheCountsBack($this->countedContacts()->where('vault_id', $vault->id), 'structure_version_id', StructureVersion::class, 'record_count');
        $this->giveTheCountsBack(Entity::query()->where('vault_id', $vault->id), 'structure_version_id', StructureVersion::class, 'record_count');
        $this->giveTheCountsBack(Entity::query()->where('vault_id', $vault->id), 'entity_type_id', EntityType::class, 'entity_count');
        $this->giveTheCountsBack(LifeEvent::query()->where('vault_id', $vault->id), 'structure_version_id', StructureVersion::class, 'record_count');

        foreach ([Contact::class, Entity::class, LifeEvent::class] as $class) {

            FieldValueRow::query()
                ->where('valuable_type', new $class()->getMorphClass())
                ->whereIn('valuable_id', $class::withTrashed()->where('vault_id', $vault->id)->select('id'))
                ->delete();

            $class::withdrawFromSearch($class::withTrashed()->where('vault_id', $vault->id));
        }

        $vault->forceDelete();
    }

    private function removeTheOwnTypes(): void
    {
        $lifeEventTypes = LifeEventType::withTrashed()
            ->whereIn('id', $this->recordsOf(LifeEventType::class))
            ->whereNotIn('id', LifeEvent::withTrashed()->select('life_event_type_id'))
            ->pluck('id');

        Structure::query()
            ->where('owner_type', new LifeEventType()->getMorphClass())
            ->whereIn('owner_id', $lifeEventTypes)
            ->delete();

        LifeEventType::withTrashed()->whereIn('id', $lifeEventTypes)->forceDelete();

        RelationshipType::withTrashed()
            ->whereIn('id', $this->recordsOf(RelationshipType::class))
            ->whereNotIn('id', Relationship::withTrashed()->select('relationship_type_id'))
            ->forceDelete();
    }

    private function removeTheCopiesOfStructures(): void
    {
        $lifeEventType = new LifeEventType()->getMorphClass();

        $structures = Structure::query()
            ->whereIn('id', StructureVersion::query()->whereIn('id', $this->recordsOf(StructureVersion::class))->select('structure_id'))
            ->get();

        $versions = StructureVersion::query()->whereIn('structure_id', $structures->modelKeys())->pluck('structure_id', 'id');

        $usedStructures = collect([Contact::withTrashed(), Entity::withTrashed(), LifeEvent::withTrashed(), RecordSnapshot::query(), VaultDefaultTemplate::query()])
            ->flatMap(fn (Builder $records): array => $records->whereIn('structure_version_id', $versions->keys())->distinct()->pluck('structure_version_id')->all())
            ->map(fn (int $versionId): int => $versions[$versionId]);

        $structures = $structures->reject(fn (Structure $structure): bool => $usedStructures->contains($structure->id));

        $ledger = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('record_type', new StructureVersion()->getMorphClass())
            ->whereIn('record_id', StructureVersion::query()->whereIn('structure_id', $structures->modelKeys())->select('id'))
            ->get(['payload'])
            ->map(fn (AccountImportItem $item): array => $item->payload ?? []);

        Template::withTrashed()->whereIn('id', $structures->where('owner_type', new Template()->getMorphClass())->pluck('owner_id'))->forceDelete();
        LifeEventType::withTrashed()->whereIn('id', $structures->where('owner_type', $lifeEventType)->pluck('owner_id'))->forceDelete();
        Structure::query()->whereIn('id', $structures->modelKeys())->delete();

        Section::withTrashed()
            ->whereIn('id', SectionVersion::query()->whereIn('id', $ledger->flatMap(fn (array $made): array => array_values((array) ($made['sections'] ?? []))))->select('section_id'))
            ->forceDelete();

        ValueList::withTrashed()->whereIn('id', $ledger->flatMap(fn (array $made): array => (array) ($made['value_lists'] ?? [])))->forceDelete();
        EntityType::withTrashed()->whereIn('id', $ledger->flatMap(fn (array $made): array => (array) ($made['entity_types'] ?? [])))->forceDelete();
    }

    private function removeTheCategoriesItAdded(): void
    {
        $added = AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->whereNotNull('payload->value_list_items')
            ->select(['id', 'payload'])
            ->lazyById(500)
            ->flatMap(fn (AccountImportItem $item): array => (array) ($item->payload['value_list_items'] ?? []));

        ValueListItem::withTrashed()
            ->whereIn('id', $added)
            ->where('usage_count', 0)
            ->forceDelete();
    }

    /** @param Builder<AccountImportItem> $ids */
    private function removeTheValuesOf(string $morphClass, Builder $ids): void
    {
        $values = FieldValue::query()
            ->where('valuable_type', $morphClass)
            ->whereIn('valuable_id', $ids);

        $this->releaseTheChoicesOf($values->clone());
        $values->delete();

        FieldValueRow::query()
            ->where('valuable_type', $morphClass)
            ->whereIn('valuable_id', $ids)
            ->delete();
    }

    /** @return Builder<Contact> */
    private function countedContacts(): Builder
    {
        return Contact::withTrashed()->where(fn (Builder $query) => $query
            ->whereNull('deleted_at')
            ->orWhereIn('id', ContactMerge::query()->whereNull('undone_at')->select('merged_contact_id')));
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

    /**
     * @param  Builder<Contact>|Builder<Entity>|Builder<LifeEvent>  $records
     * @param  class-string<Model>  $model
     */
    private function giveTheCountsBack(Builder $records, string $column, string $model, string $counter): void
    {
        $counts = $records
            ->groupBy($column)
            ->selectRaw("{$column}, count(*) as total")
            ->pluck('total', $column);

        foreach ($counts as $id => $total) {
            $model::query()
                ->whereKey($id)
                ->where($counter, '>=', $total)
                ->decrement($counter, $total);
        }
    }

    /** @param Collection<int, File> $files */
    private function removeFromStorage(Collection $files): void
    {
        $this->removedFiles = [...$this->removedFiles, ...$files->all()];

        $bytes = (int) $files->sum('size_in_bytes');

        foreach (array_chunk($files->modelKeys(), 1000) as $ids) {
            File::withTrashed()->whereIn('id', $ids)->forceDelete();
        }

        Account::query()
            ->whereKey($this->import->account_id)
            ->where('storage_used_in_bytes', '>=', $bytes)
            ->decrement('storage_used_in_bytes', $bytes);
    }

    /** @return Builder<AccountImportItem> */
    private function recordsOf(string $class): Builder
    {
        return AccountImportItem::query()
            ->where('account_import_id', $this->import->id)
            ->where('record_type', new $class()->getMorphClass())
            ->select('record_id');
    }
}
