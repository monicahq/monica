<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a section up or down a template version.
 *
 * The section is taken out and put back at the position asked for, and every
 * section on that version is then numbered from one again. A position past the
 * end puts it last.
 *
 * Only on a draft: the order of a published version is part of what was
 * published.
 */
class UpdateTemplateVersionSectionPosition
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionSection $row,
        private readonly int $position,
    ) {}

    public function execute(): TemplateVersionSection
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This assembly is in another account.');
        }
    }

    private function update(): void
    {
        $rows = TemplateVersionSection::query()
            ->where('template_version_id', $this->row->template_version_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (TemplateVersionSection $row): bool => $row->is($this->row))
            ->values();

        $rows->splice(max(0, $this->position - 1), 0, [$this->row]);

        DB::transaction(function () use ($rows): void {
            $rows->each(function (TemplateVersionSection $row, int $index): void {
                $row->position = $index + 1;
                $row->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateSectionPositionUpdate,
            parameters: [
                'section' => $this->row->sectionVersion->section->name,
                'name' => $this->row->templateVersion->template->name,
            ],
            loggable: $this->row->templateVersion->template,
        )->onQueue('low');
    }
}
