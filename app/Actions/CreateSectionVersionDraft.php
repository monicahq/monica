<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Start a new draft of a section, copied from a version that already exists.
 */
class CreateSectionVersionDraft
{
    private SectionVersion $draft;

    public function __construct(
        private readonly User $user,
        private readonly SectionVersion $sectionVersion,
    ) {}

    public function execute(): SectionVersion
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->draft;
    }

    private function validate(): void
    {
        if ($this->sectionVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This section version is in another account.');
        }
    }

    private function create(): void
    {
        $highest = (int) SectionVersion::query()
            ->where('section_id', $this->sectionVersion->section_id)
            ->max('version_number');

        DB::transaction(function () use ($highest): void {
            $this->draft = SectionVersion::query()->create([
                'account_id' => $this->sectionVersion->account_id,
                'section_id' => $this->sectionVersion->section_id,
                'version_number' => $highest + 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'resolved_value_lists' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => $this->sectionVersion->id,
                'usage_count' => 0,
            ]);

            $this->copyFields();
        });
    }

    private function copyFields(): void
    {
        $newIds = [];

        $fields = Field::query()
            ->where('section_version_id', $this->sectionVersion->id)
            ->orderByRaw('parent_field_id is not null')
            ->orderBy('position')
            ->get();

        foreach ($fields as $field) {
            $copy = $field->replicate(['section_version_id', 'parent_field_id']);
            $copy->section_version_id = $this->draft->id;
            $copy->parent_field_id = $field->parent_field_id === null ? null : $newIds[$field->parent_field_id];
            $copy->save();

            $newIds[$field->id] = $copy->id;
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SectionVersionDrafted,
            parameters: [
                'name' => $this->draft->section->name,
                'version' => $this->draft->version_number,
            ],
            loggable: $this->draft->section,
        )->onQueue('low');
    }
}
