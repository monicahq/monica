<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Helpers\StructureFile;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Number;

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

    /** @return array<string, mixed> */
    public function file(): array
    {
        return once(fn (): array => StructureFile::ofTemplate($this->template));
    }

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
        return trans_choice(
            ':count line · :size|:count lines · :size',
            substr_count($this->json(), "\n") + 1,
            ['size' => $this->sizeLabel()],
        );
    }

    public function structureLabel(): string
    {
        return __(':sections, :fields, :lists', [
            'sections' => trans_choice(':count section|:count sections', $this->sectionCount()),
            'fields' => trans_choice(':count field|:count fields', $this->fieldCount()),
            'lists' => trans_choice(':count value list|:count value lists', $this->valueListCount()),
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

    /** @return array<int, array{label: string, value: string}> */
    public function inFile(): array
    {
        return [
            ['label' => __('Structure'), 'value' => $this->structureLabel()],
            ['label' => __('Version'), 'value' => $this->versionLabel()],
            ['label' => __('Format'), 'value' => __('Monica template format :version', ['version' => StructureFile::FORMAT_VERSION])],
            ['label' => __('Size'), 'value' => $this->sizeLabel()],
        ];
    }

    public function recordCountLabel(): string
    {
        $count = $this->template->publishedVersion->record_count;

        return trans_choice(':count record|:count records', $count);
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
