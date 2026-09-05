<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Add a page to a template version, at the end of it.
 */
class CreateTemplateVersionPage
{
    private TemplateVersionPage $page;

    public function __construct(
        private readonly User $user,
        private readonly TemplateVersion $templateVersion,
        private string $label,
        private ?string $key = null,
    ) {}

    public function execute(): TemplateVersionPage
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->page;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::plainText($this->label);
        $this->key = TextSanitizer::nullablePlainText($this->key);
    }

    private function validate(): void
    {
        if ($this->templateVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template version is in another account.');
        }

        $taken = $this->pagesOfTheVersion()
            ->contains(fn (TemplateVersionPage $page): bool => mb_strtolower($page->label) === mb_strtolower($this->label));

        if ($taken) {
            throw new PageLabelIsAlreadyUsed('This template version already has a page reading exactly that.');
        }
    }

    private function create(): void
    {
        $positions = TemplateVersionPage::query()
            ->where('template_version_id', $this->templateVersion->id)
            ->pluck('position');

        $this->page = TemplateVersionPage::query()->create([
            'account_id' => $this->templateVersion->account_id,
            'template_version_id' => $this->templateVersion->id,
            'key' => $this->key(),
            'label' => $this->label,
            'label_translation_key' => null,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    /** @return Collection<int, TemplateVersionPage> */
    private function pagesOfTheVersion(): Collection
    {
        return TemplateVersionPage::query()
            ->where('template_version_id', $this->templateVersion->id)
            ->get();
    }

    private function key(): string
    {
        return SlugBuilder::build(
            wanted: $this->key,
            derivedFrom: $this->label,
            maxLength: TemplateVersionPage::KEY_LENGTH,
            whenEmpty: 'page',
            isTaken: fn (string $key): bool => TemplateVersionPage::query()
                ->where('template_version_id', $this->templateVersion->id)
                ->where('key', $key)
                ->exists(),
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplatePageCreation,
            parameters: [
                'label' => $this->page->label,
                'name' => $this->templateVersion->template->name,
            ],
            loggable: $this->templateVersion->template,
        )->onQueue('low');
    }
}
