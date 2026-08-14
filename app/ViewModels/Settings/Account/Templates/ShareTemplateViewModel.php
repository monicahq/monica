<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Helpers\StructureFile;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Number;

/**
 * The two ways a template leaves an account, and the file that is one of them.
 *
 * One view model serves both screens because they read the same thing. What the
 * share screen quotes about the file (its name, its weight, how much structure
 * is in it) is what the export screen shows in full, and building the payload
 * twice from two classes would be two chances for the two screens to disagree
 * about what is about to leave.
 *
 * Everything here is read from the published version. A draft has nothing
 * frozen to send, and the screens are not reachable without one.
 */
class ShareTemplateViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function template(): Template
    {
        return $this->template;
    }

    /**
     * The file itself. It is built once per request: the payload walks every
     * page, every section version and every field, and both screens ask for it
     * more than once.
     *
     * @return array<string, mixed>
     */
    public function file(): array
    {
        return once(fn (): array => StructureFile::ofTemplate($this->template));
    }

    /**
     * The file as text, which is the same text the download hands over. What is
     * read on the screen has to be what lands on the disk, or reading it first
     * proves nothing.
     */
    public function json(): string
    {
        return once(fn (): string => StructureFile::encode($this->file()));
    }

    public function fileName(): string
    {
        return StructureFile::nameOfTemplate($this->template);
    }

    public function sizeLabel(): string
    {
        return Number::fileSize(strlen($this->json()), precision: 1);
    }

    public function lineLabel(): string
    {
        return __(':count lines · :size', [
            'count' => substr_count($this->json(), "\n") + 1,
            'size' => $this->sizeLabel(),
        ]);
    }

    /**
     * What is in the file, counted. It is the one thing somebody can check at a
     * glance against what they think they built.
     */
    public function structureLabel(): string
    {
        return __(':sections, :fields, :lists', [
            'sections' => $this->sectionCount() === 1
                ? __('1 section')
                : __(':count sections', ['count' => $this->sectionCount()]),
            'fields' => $this->fieldCount() === 1
                ? __('1 field')
                : __(':count fields', ['count' => $this->fieldCount()]),
            'lists' => $this->valueListCount() === 1
                ? __('1 list of choices')
                : __(':count lists of choices', ['count' => $this->valueListCount()]),
        ]);
    }

    public function versionLabel(): string
    {
        $version = $this->template->publishedVersion;

        return __('v:version, published :date', [
            'version' => $version?->version_number,
            'date' => $version?->published_at?->isoFormat('LL') ?? '',
        ]);
    }

    /**
     * The rows of the panel that says what is in the file, in the order
     * somebody would ask: what shape, which version, in what format, how heavy.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function inFile(): array
    {
        return [
            ['label' => __('Structure'), 'value' => $this->structureLabel()],
            ['label' => __('Version'), 'value' => $this->versionLabel()],
            ['label' => __('Format'), 'value' => __('Monica template format :version', ['version' => StructureFile::FORMAT_VERSION])],
            ['label' => __('Size'), 'value' => $this->sizeLabel()],
        ];
    }

    /**
     * How many records are on the published version. The share screen says the
     * number out loud beside the promise that none of them travels: a count is
     * harder to disbelieve than a reassurance.
     */
    public function recordCountLabel(): string
    {
        $count = $this->template->publishedVersion->record_count;

        return $count === 1
            ? __('1 record')
            : __(':count records', ['count' => $count]);
    }

    public function sectionCount(): int
    {
        return array_sum(array_map(
            fn (array $page): int => count($page['sections']),
            $this->file()['template']['pages'],
        ));
    }

    public function fieldCount(): int
    {
        $count = 0;

        foreach ($this->file()['template']['pages'] as $page) {
            foreach ($page['sections'] as $row) {
                $count += count($row['section']['fields']);
            }
        }

        return $count;
    }

    public function valueListCount(): int
    {
        return count($this->file()['value_lists']);
    }

    public function shareUrl(): string
    {
        return route('settings.templateShare.index', $this->template);
    }

    public function exportUrl(): string
    {
        return route('settings.templateExport.index', $this->template);
    }

    public function downloadUrl(): string
    {
        return route('settings.templateExport.show', $this->template);
    }

    public function builderUrl(): string
    {
        return route('settings.templateBuilder.index', $this->template);
    }
}
