<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SectionVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Get hold of a version of a section that can be written to, for somebody who has just started
 * editing a field on one particular template.
 */
class MakeSectionVersionEditable
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersionSection $row,
    ) {}

    public function execute(): SectionVersion
    {
        $this->validate();

        if ($this->isAlreadyEditable()) {
            return $this->row->sectionVersion;
        }

        return $this->draft();
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function isAlreadyEditable(): bool
    {
        $version = $this->row->sectionVersion;

        return $version->status->isEditable() && $version->usage_count <= 1;
    }

    private function draft(): SectionVersion
    {
        $previous = $this->row->sectionVersion;

        return DB::transaction(function () use ($previous): SectionVersion {
            $draft = new CreateSectionVersionDraft(
                user: $this->user,
                sectionVersion: $previous,
            )->execute();

            $this->row->section_version_id = $draft->id;
            $this->row->save();

            SectionVersion::query()->whereKey($draft->id)->increment('usage_count');

            SectionVersion::query()
                ->whereKey($previous->id)
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');

            return $draft->refresh();
        });
    }
}
