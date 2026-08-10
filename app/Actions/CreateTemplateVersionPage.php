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
 * Add a page to a template version, at the end of it. A record made from a
 * version with several of them reads as tabs, in this order.
 *
 * The key is settled here and never rebuilt. It is what a record URL carries
 * and what every later draft copied from this version keeps, so renaming
 * "Health" to "Medical" leaves it alone and a link that already went out still
 * lands on the right tab.
 *
 * Two pages of one version cannot read the same, since the label is the only
 * thing telling one tab from another for whoever navigates by name.
 *
 * Only on a draft: the pages of a published version are part of what was
 * published, and the model refuses the write.
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

    /**
     * The pages of the version, read now rather than off the model.
     *
     * A supplied page reads by its translation key, so what is compared is the
     * label as somebody sees it rather than the column, and that comparison
     * happens here rather than in the query.
     *
     * @return Collection<int, TemplateVersionPage>
     */
    private function pagesOfTheVersion(): Collection
    {
        return TemplateVersionPage::query()
            ->where('template_version_id', $this->templateVersion->id)
            ->get();
    }

    /**
     * Settle the key, keeping it unique within the version. It reads in dashes
     * rather than underscores, unlike the key of a field: this one is read by a
     * person, in the address bar.
     */
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
