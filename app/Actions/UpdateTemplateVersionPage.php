<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a page.
 *
 * The key is left exactly as it is. It is what record URLs carry and what later
 * drafts keep, and a tab that changes address because somebody preferred
 * another word is a link that stops working.
 *
 * A page the product supplied reads by its translation key until this action
 * runs. From the moment somebody renames it, it reads in their words in every
 * language, which is what renaming means.
 *
 * Only on a draft, which the model enforces.
 */
class UpdateTemplateVersionPage
{
    public function __construct(
        private readonly User $user,
        private readonly TemplateVersionPage $page,
        private string $label,
    ) {}

    public function execute(): TemplateVersionPage
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

        $taken = TemplateVersionPage::query()
            ->where('template_version_id', $this->page->template_version_id)
            ->get()
            ->reject(fn (TemplateVersionPage $page): bool => $page->is($this->page))
            ->contains(fn (TemplateVersionPage $page): bool => mb_strtolower($page->label) === mb_strtolower($this->label));

        if ($taken) {
            throw new PageLabelIsAlreadyUsed('This template version already has a page reading exactly that.');
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
                'name' => $this->page->templateVersion->template->name,
            ],
            loggable: $this->page->templateVersion->template,
        )->onQueue('low');
    }
}
