<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Helpers\SlugBuilder;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Add a page to a structure version, at the end of it.
 */
class CreateStructureVersionPage
{
    private StructureVersionPage $page;

    public function __construct(
        private readonly User $user,
        private readonly StructureVersion $structureVersion,
        private string $label,
        private ?string $key = null,
    ) {}

    public function execute(): StructureVersionPage
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
        if ($this->structureVersion->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This structure version is in another account.');
        }

        $taken = $this->pagesOfTheVersion()
            ->contains(fn (StructureVersionPage $page): bool => mb_strtolower($page->label) === mb_strtolower($this->label));

        if ($taken) {
            throw new PageLabelIsAlreadyUsed('This structure version already has a page reading exactly that.');
        }
    }

    private function create(): void
    {
        $positions = StructureVersionPage::query()
            ->where('structure_version_id', $this->structureVersion->id)
            ->pluck('position');

        $this->page = StructureVersionPage::query()->create([
            'account_id' => $this->structureVersion->account_id,
            'structure_version_id' => $this->structureVersion->id,
            'key' => $this->key(),
            'label' => $this->label,
            'label_translation_key' => null,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    /** @return Collection<int, StructureVersionPage> */
    private function pagesOfTheVersion(): Collection
    {
        return StructureVersionPage::query()
            ->where('structure_version_id', $this->structureVersion->id)
            ->get();
    }

    private function key(): string
    {
        return SlugBuilder::build(
            wanted: $this->key,
            derivedFrom: $this->label,
            maxLength: StructureVersionPage::KEY_LENGTH,
            whenEmpty: 'page',
            isTaken: fn (string $key): bool => StructureVersionPage::query()
                ->where('structure_version_id', $this->structureVersion->id)
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
                'name' => $this->structureVersion->structure->owner->name,
            ],
            loggable: $this->structureVersion->structure->owner,
        );
    }
}
