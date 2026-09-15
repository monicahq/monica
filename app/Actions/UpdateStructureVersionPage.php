<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a page.
 */
class UpdateStructureVersionPage
{
    public function __construct(
        private readonly User $user,
        private readonly StructureVersionPage $page,
        private string $label,
    ) {}

    public function execute(): StructureVersionPage
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->page;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::plainText($this->label);
    }

    private function validate(): void
    {
        if ($this->page->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This page is in another account.');
        }

        $taken = StructureVersionPage::query()
            ->where('structure_version_id', $this->page->structure_version_id)
            ->get()
            ->reject(fn (StructureVersionPage $page): bool => $page->is($this->page))
            ->contains(fn (StructureVersionPage $page): bool => mb_strtolower($page->label) === mb_strtolower($this->label));

        if ($taken) {
            throw new PageLabelIsAlreadyUsed('This structure version already has a page reading exactly that.');
        }
    }

    private function update(): void
    {
        $this->page->label = $this->label;
        $this->page->label_translation_key = null;
        $this->page->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplatePageUpdate,
            parameters: [
                'label' => $this->page->label,
                'name' => $this->page->structureVersion->structure->owner->name,
            ],
            loggable: $this->page->structureVersion->structure->owner,
        )->onQueue('low');
    }
}
