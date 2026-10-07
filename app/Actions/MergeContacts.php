<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactChangedSinceThePreview;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\ContactNameRepresentation;
use App\Models\FieldValue;
use App\Models\RecordSnapshot;
use App\Models\Reminder;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Make two contacts one, keeping everything either of them held.
 *
 * It follows the shape of {@see MoveRecordToStructureVersion}, and destroys nothing: the absorbed
 * contact is soft deleted and keeps its rows. Every decision arrives in `$resolutions` and must
 * match what {@see PreviewContactMerge} reported. Snapshots are left where they are.
 */
class MergeContacts
{
    private ContactMerge $merge;

    private RecordSnapshot $snapshot;

    /** @var list<array<string, mixed>> */
    private array $ledger = [];

    /**
     * @param  list<array{field_key: string, row?: int|null, keep: string}>  $resolutions
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact $kept,
        private readonly Contact $absorbed,
        private readonly array $resolutions = [],
        private readonly ?string $fingerprint = null,
    ) {}

    public function execute(): ContactMerge
    {
        $preview = new PreviewContactMerge(
            user: $this->user,
            kept: $this->kept,
            absorbed: $this->absorbed,
        )->execute();

        $this->guardTheState($preview);
        $decisions = $this->decisions($preview);

        $this->snapshot = new CreateRecordSnapshot(
            user: $this->user,
            record: $this->kept,
            reason: SnapshotReasonEnum::Merge,
        )->execute();

        DB::transaction(function () use ($decisions): void {
            $this->takeTheAvatar($decisions);
            $this->takeTheNames($decisions);
            $this->takeTheValues($decisions);
            $this->takeTheRows($decisions);
            $this->takeThePointers();
            $this->takeTheReminders();
            $this->takeTheShareLinks();
            $this->setAsideWhatTheStructureDoesNotCarry();
            $this->stopBeingPartial();

            $this->absorbed->delete();

            $this->record();
        });

        $this->log();

        return $this->merge;
    }

    private function guardTheState(array $preview): void
    {
        if ($this->fingerprint === null) {
            return;
        }

        if (! hash_equals($preview['fingerprint'], $this->fingerprint)) {
            throw new ContactChangedSinceThePreview('One of these contacts changed while the merge was being reviewed.');
        }
    }

    /** @return array<string, string> */
    private function decisions(array $preview): array
    {
        $asked = [];

        foreach ($preview['conflicts'] as $conflict) {
            $asked[$this->key($conflict['field_key'], $conflict['row'])] = ['kept', 'other'];
        }

        foreach ($preview['collisions'] as $collision) {
            $asked[$this->key($collision['field_key'], $collision['row'])] = ['kept', 'both'];
        }

        $given = [];

        foreach ($this->resolutions as $resolution) {
            $key = $this->key($resolution['field_key'], $resolution['row'] ?? null);

            if (! isset($asked[$key])) {
                throw ValidationException::withMessages([
                    'resolutions' => __('The preview did not ask about :field.', ['field' => $resolution['field_key']]),
                ]);
            }

            if (isset($given[$key])) {
                throw ValidationException::withMessages([
                    'resolutions' => __(':field was decided twice.', ['field' => $resolution['field_key']]),
                ]);
            }

            if (! in_array($resolution['keep'], $asked[$key], true)) {
                throw ValidationException::withMessages([
                    'resolutions' => __(':field cannot be resolved that way.', ['field' => $resolution['field_key']]),
                ]);
            }

            $given[$key] = $resolution['keep'];
        }

        $undecided = array_diff(array_keys($asked), array_keys($given), [$this->key(PreviewContactMerge::DISPLAY_NAME_KEY, null)]);

        if ($undecided !== []) {
            throw ValidationException::withMessages([
                'resolutions' => __('Every difference has to be decided before the merge can run.'),
            ]);
        }

        return $given;
    }

    private function takeTheAvatar(array $decisions): void
    {
        $wanted = $decisions[$this->key(PreviewContactMerge::AVATAR_KEY, null)] ?? null;

        if ($wanted !== 'other' && ! ($this->kept->avatar_file_id === null && $this->absorbed->avatar_file_id !== null)) {
            return;
        }

        $this->ledger[] = ['kind' => 'avatar_replaced', 'was' => $this->kept->avatar_file_id];

        $this->kept->avatar_file_id = $this->absorbed->avatar_file_id;
        $this->kept->save();
    }

    /** @param array<string, string> $decisions */
    private function takeTheNames(array $decisions): void
    {
        $kept = $this->kept->nameRepresentations()->get();
        $position = (int) $kept->max('position');
        $absorbedDisplayId = $this->absorbed->display_name_representation_id;
        $replacement = null;

        foreach ($this->absorbed->nameRepresentations()->get() as $representation) {
            $twin = $kept->first(fn (ContactNameRepresentation $mine): bool => $mine->toComparable() === $representation->toComparable());

            if ($twin !== null) {
                $replacement = $representation->id === $absorbedDisplayId ? $twin : $replacement;

                continue;
            }

            $this->ledger[] = ['kind' => 'name_representation_moved', 'id' => $representation->id, 'from' => $this->absorbed->id, 'position' => $representation->position];

            $representation->contact()->associate($this->kept);
            $representation->position = ++$position;
            $representation->save();

            $replacement = $representation->id === $absorbedDisplayId ? $representation : $replacement;
        }

        $wanted = $decisions[$this->key(PreviewContactMerge::DISPLAY_NAME_KEY, null)] ?? null;
        $keptHasNoName = $this->kept->display_name_representation_id === null;

        if ($replacement === null || ($wanted !== 'other' && ! $keptHasNoName)) {
            return;
        }

        $this->ledger[] = ['kind' => 'display_name_replaced', 'was' => $this->kept->display_name_representation_id];

        $this->kept->display_name_representation_id = $replacement->id;
        $this->kept->save();
    }

    private function takeTheValues(array $decisions): void
    {
        $carried = $this->fieldsTheSurvivorCarries();
        $mine = $this->kept->fieldValues()->attached()->whereNull('field_value_row_id')->get()->keyBy('field_id');

        $theirs = $this->absorbed->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->whereIn('field_id', $carried)
            ->with('field')
            ->get();

        foreach ($theirs as $value) {
            $held = $mine->get($value->field_id);

            if ($held !== null && ($decisions[$this->key($value->field_key, null)] ?? 'kept') !== 'other') {
                continue;
            }

            if ($held !== null) {
                $held->is_detached = true;
                $held->detached_at = now();
                $held->save();

                $this->ledger[] = ['kind' => 'value_detached', 'id' => $held->id];

                $rule = $held->reminder;

                if ($rule instanceof Reminder && $rule->is_active) {
                    $this->ledger[] = ['kind' => 'reminder_deactivated', 'id' => $rule->id];

                    new DeactivateReminders(user: $this->user, subject: $rule, reason: ReminderInactiveReasonEnum::ValueCleared)->execute();
                }
            }

            $value->valuable_id = $this->kept->id;
            $value->save();

            $this->ledger[] = ['kind' => 'value_moved', 'id' => $value->id, 'from' => $this->absorbed->id];
        }
    }

    private function takeTheRows(array $decisions): void
    {
        $rows = $this->absorbed->fieldValueRows()->with('field')->get();

        foreach ($rows as $row) {
            if (($decisions[$this->key($row->field->key, $row->id)] ?? 'both') === 'kept') {
                continue;
            }

            $row->valuable_id = $this->kept->id;
            $row->save();

            FieldValue::query()
                ->where('field_value_row_id', $row->id)
                ->update(['valuable_id' => $this->kept->id]);

            $this->ledger[] = ['kind' => 'row_moved', 'id' => $row->id, 'from' => $this->absorbed->id];
        }
    }

    private function takeThePointers(): void
    {
        $pointing = FieldValue::query()
            ->where('related_contact_id', $this->absorbed->id)
            ->pluck('id')
            ->all();

        if ($pointing === []) {
            return;
        }

        FieldValue::query()->whereIn('id', $pointing)->update(['related_contact_id' => $this->kept->id]);

        foreach ($pointing as $id) {
            $this->ledger[] = ['kind' => 'pointer_moved', 'id' => $id, 'from' => $this->absorbed->id];
        }
    }

    private function takeTheReminders(): void
    {
        $reminders = Reminder::query()
            ->where('remindable_type', $this->absorbed->getMorphClass())
            ->where('remindable_id', $this->absorbed->id)
            ->get();

        foreach ($reminders as $reminder) {
            $reminder->remindable_id = $this->kept->id;
            $reminder->save();

            $this->ledger[] = ['kind' => 'reminder_moved', 'id' => $reminder->id, 'from' => $this->absorbed->id];

            if ($reminder->source === ReminderSourceEnum::Manual) {
                continue;
            }

            $source = $reminder->field_value_id === null
                ? null
                : FieldValue::query()->find($reminder->field_value_id);

            if ($source !== null && $source->valuable_id === $this->kept->id && ! $source->is_detached) {
                continue;
            }

            $this->ledger[] = ['kind' => 'reminder_deactivated', 'id' => $reminder->id];

            new DeactivateReminders(
                user: $this->user,
                subject: $reminder,
                reason: ReminderInactiveReasonEnum::ValueCleared,
            )->execute();
        }
    }

    private function takeTheShareLinks(): void
    {
        $links = ShareLink::query()
            ->where('shareable_type', $this->absorbed->getMorphClass())
            ->where('shareable_id', $this->absorbed->id)
            ->pluck('id')
            ->all();

        if ($links === []) {
            return;
        }

        ShareLink::query()->whereIn('id', $links)->update(['shareable_id' => $this->kept->id]);

        foreach ($links as $id) {
            $this->ledger[] = ['kind' => 'share_link_moved', 'id' => $id, 'from' => $this->absorbed->id];
        }
    }

    private function setAsideWhatTheStructureDoesNotCarry(): void
    {
        $leaving = FieldValue::query()
            ->where('valuable_type', $this->absorbed->getMorphClass())
            ->where('valuable_id', $this->absorbed->id)
            ->where('is_detached', false)
            ->whereNotIn('field_id', $this->fieldsTheSurvivorCarries())
            ->get();

        foreach ($leaving as $value) {
            $value->valuable_id = $this->kept->id;
            $value->is_detached = true;
            $value->detached_at = now();
            $value->save();

            $this->ledger[] = ['kind' => 'value_set_aside', 'id' => $value->id, 'from' => $this->absorbed->id];
        }
    }

    private function stopBeingPartial(): void
    {
        if (! $this->kept->is_partial) {
            return;
        }

        $this->kept->is_partial = false;
        $this->kept->save();

        $this->ledger[] = ['kind' => 'partial_cleared', 'id' => $this->kept->id];
    }

    private function record(): void
    {
        $this->merge = ContactMerge::query()->create([
            'account_id' => $this->kept->account_id,
            'vault_id' => $this->kept->vault_id,
            'kept_contact_id' => $this->kept->id,
            'merged_contact_id' => $this->absorbed->id,
            'resolutions' => $this->resolutions,
            'ledger' => $this->ledger,
            'snapshot_id' => $this->snapshot->id,
            'merged_by_user_id' => $this->user->id,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactsMerged,
            vault: $this->kept->vault,
            parameters: [
                'name' => $this->kept->name,
                'other' => $this->absorbed->name,
            ],
            loggable: $this->kept,
        )->onQueue('low');
    }

    /** @return list<int> */
    private function fieldsTheSurvivorCarries(): array
    {
        return once(fn (): array => $this->kept->structureVersion->assembly
            ->flatMap(fn ($row): array => $row->sectionVersion->fields->pluck('id')->all())
            ->all());
    }

    private function key(string $fieldKey, ?int $row): string
    {
        return $fieldKey.':'.($row ?? '');
    }
}
