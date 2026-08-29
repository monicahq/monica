<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\RecordSnapshot;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Capture the complete state of a record, so somebody can come back to it.
 *
 * It is taken before a structure change, before a merge and before an import
 * overwrites anything, and it can be taken by hand. Restoring is itself an
 * action that takes one first, so a restore can be undone.
 *
 * The payload is self contained, which is the whole difficulty. It carries the
 * pages, the section placements and the fields as they read at the time, and
 * not only the values, because the structure it came from may have been retired
 * by the time anybody wants it back. What it does keep of the structure are the
 * identifiers as well as the labels: a published version is frozen and its
 * fields cannot be deleted, so the identifiers are what puts the values back,
 * and the labels are what makes the payload readable without it.
 */
class CreateRecordSnapshot
{
    private RecordSnapshot $snapshot;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
        private readonly SnapshotReasonEnum $reason = SnapshotReasonEnum::Manual,
        private readonly ?CarbonImmutable $expiresAt = null,
    ) {}

    public function execute(): RecordSnapshot
    {
        $this->validate();
        $this->capture();
        $this->log();

        return $this->snapshot;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }
    }

    private function capture(): void
    {
        $values = $this->values();

        $this->snapshot = RecordSnapshot::query()->create([
            'account_id' => $this->record->account_id,
            'vault_id' => $this->record->vault_id,
            'snapshotable_type' => $this->record->getMorphClass(),
            'snapshotable_id' => $this->record->id,
            'template_version_id' => $this->record->template_version_id,
            'reason' => $this->reason,
            'payload' => [
                'record' => $this->record(),
                'structure' => $this->structure(),
                'rows' => $this->rows(),
                'values' => $values,
            ],
            'file_ids' => $this->fileIds($values),
            'created_by_user_id' => $this->user->id,
            'expires_at' => $this->expiresAt,
        ]);
    }

    /**
     * What the record itself is, which for a contact is every part of the name
     * and for an entity is the one it has.
     *
     * @return array<string, mixed>
     */
    private function record(): array
    {
        $common = [
            'type' => $this->record->getMorphClass(),
            'uuid' => $this->record->uuid,
            'avatar_file_id' => $this->record->avatar_file_id,
            'avatar_colour' => $this->record->avatar_colour,
            'is_archived' => $this->record->is_archived,
        ];

        if ($this->record instanceof Entity) {
            return array_merge($common, [
                'name' => $this->record->name,
                'entity_type_id' => $this->record->entity_type_id,
            ]);
        }

        return array_merge($common, [
            'prefix' => $this->record->prefix,
            'first_name' => $this->record->first_name,
            'middle_name' => $this->record->middle_name,
            'last_name' => $this->record->last_name,
            'suffix' => $this->record->suffix,
            'maiden_name' => $this->record->maiden_name,
            'nickname' => $this->record->nickname,
            'name_order' => $this->record->name_order?->value,
            'is_me' => $this->record->is_me,
            'is_partial' => $this->record->is_partial,
            'is_favourite' => $this->record->is_favourite,
        ]);
    }

    /**
     * The structure as it read at the time: the pages, what sat on each of
     * them, and the fields inside those.
     *
     * @return array<int, array<string, mixed>>
     */
    private function structure(): array
    {
        $version = $this->record->templateVersion;
        $assembly = $version->assembly->load('sectionVersion.section', 'sectionVersion.fields');

        return $version->pages
            ->map(fn (TemplateVersionPage $page): array => [
                'id' => $page->id,
                'key' => $page->key,
                'label' => $page->label,
                'position' => $page->position,
                'sections' => $assembly
                    ->where('template_version_page_id', $page->id)
                    ->map(fn (TemplateVersionSection $row): array => [
                        'id' => $row->id,
                        'name' => $row->title_override ?? $row->sectionVersion->section->name,
                        'position' => $row->position,
                        'is_collapsed_by_default' => $row->is_collapsed_by_default,
                        'fields' => $row->sectionVersion->fields
                            ->map(fn (Field $field): array => [
                                'id' => $field->id,
                                'key' => $field->key,
                                'label' => $field->label,
                                'type' => $field->type->value,
                                'position' => $field->position,
                                'parent_field_id' => $field->parent_field_id,
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * The repetitions of every repeater on the record, so that what went
     * together goes back together.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->orderBy('field_id')
            ->orderBy('position')
            ->get()
            ->map(fn (FieldValueRow $row): array => [
                'id' => $row->id,
                'field_id' => $row->field_id,
                'position' => $row->position,
            ])
            ->all();
    }

    /**
     * Everything written on the record, the values set aside included. A value
     * that was detached when the snapshot was taken has to come back detached,
     * or restoring would quietly put back on the page something the structure
     * had taken off it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function values(): array
    {
        return FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->orderBy('field_id')
            ->get()
            ->map(fn (FieldValue $value): array => [
                'field_id' => $value->field_id,
                'field_key' => $value->field_key,
                'field_value_row_id' => $value->field_value_row_id,
                'value_string' => $value->value_string,
                'value_text' => $value->value_text,
                'value_number' => $value->value_number,
                'value_boolean' => $value->value_boolean,
                'value_date' => $value->value_date?->format('Y-m-d'),
                'value_datetime' => $value->value_datetime?->format('Y-m-d H:i:s'),
                'value_json' => $value->value_json,
                'value_list_item_id' => $value->value_list_item_id,
                'value_list_item_ids' => $value->value_list_item_ids,
                'value_file_id' => $value->value_file_id,
                'related_contact_id' => $value->related_contact_id,
                'related_entity_id' => $value->related_entity_id,
                'year_is_unknown' => $value->year_is_unknown,
                'month_is_unknown' => $value->month_is_unknown,
                'is_detached' => $value->is_detached,
                'detached_at' => $value->detached_at?->format('Y-m-d H:i:s'),
            ])
            ->all();
    }

    /**
     * The files the snapshot names, which the orphan sweep reads before it
     * takes anything. The avatar counts: a record put back looking like
     * somebody else is not the record put back.
     *
     * @param  array<int, array<string, mixed>>  $values
     * @return list<int>
     */
    private function fileIds(array $values): array
    {
        return collect($values)
            ->pluck('value_file_id')
            ->push($this->record->avatar_file_id)
            ->filter()
            ->map(fn (int $id): int => $id)
            ->unique()
            ->values()
            ->all();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RecordSnapshotTaken,
            vault: $this->record->vault,
            parameters: ['name' => $this->record->name, 'reason' => $this->reason->value],
            loggable: $this->snapshot,
        )->onQueue('low');
    }
}
