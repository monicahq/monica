<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Helpers\StructureFile;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use App\Models\User;
use App\Models\ValueList;

class ImportFileViewModel
{
    /** @param  array<string, mixed>|null  $checked */
    public function __construct(
        private readonly User $user,
        private readonly ?Template $template = null,
        private readonly ?TemplateVersionPage $page = null,
        private readonly string $raw = '',
        private readonly ?array $checked = null,
        private readonly ?string $refusal = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function template(): ?Template
    {
        return $this->template;
    }

    public function isForASection(): bool
    {
        return $this->template !== null && $this->page !== null;
    }

    public function title(): string
    {
        return $this->isForASection()
            ? __('Import a section')
            : __('Import a template');
    }

    public function intro(): string
    {
        return $this->isForASection()
            ? __('Paste a section file below and it is added at the end of this page, ready to move where you want it. Nothing already on the template is touched.')
            : __('Anything exported out of Monica can be imported again. Paste the file below and it arrives in this account as a draft. Nothing on your records changes until you decide to use it.');
    }

    /** @return array<int, array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        if (! $this->isForASection()) {
            return [
                ['label' => __('Templates'), 'url' => route('settings.templates.index')],
                ['label' => __('Import')],
            ];
        }

        return [
            ['label' => $this->template->name, 'url' => route('settings.templateBuilder.index', $this->template)],
            ['label' => __('Import a section')],
        ];
    }

    public function raw(): string
    {
        return $this->raw;
    }

    public function formatVersion(): int
    {
        return StructureFile::FORMAT_VERSION;
    }

    public function counterLabel(): string
    {
        if (mb_trim($this->raw) === '') {
            return __('Nothing pasted yet');
        }

        return __(':lines lines · :characters characters', [
            'lines' => substr_count($this->raw, "\n") + 1,
            'characters' => mb_strlen($this->raw),
        ]);
    }

    public function isChecked(): bool
    {
        return $this->checked !== null;
    }

    public function isRefused(): bool
    {
        return $this->refusal !== null;
    }

    public function refusal(): ?string
    {
        return $this->refusal;
    }

    public function summary(): string
    {
        return $this->checked !== null && $this->checked['format'] === 'section'
            ? __('Every field in it is one this account knows how to render.')
            : __('Every section, field and value list in it is one this account knows how to render.');
    }

    /** @return array<int, array{label: string, value: string}> */
    public function found(): array
    {
        if ($this->checked === null) {
            return [];
        }

        $rows = $this->checked['format'] === 'template'
            ? $this->foundInATemplate()
            : $this->foundInASection();

        $rows[] = ['label' => __('Value lists'), 'value' => $this->valueListsLabel()];
        $rows[] = ['label' => __('Kinds of record'), 'value' => $this->entityTypesLabel()];
        $rows[] = ['label' => __('Format'), 'value' => __('Monica structure format :version', ['version' => $this->checked['format_version']])];
        $rows[] = ['label' => __('Records inside'), 'value' => __('None')];

        return $rows;
    }

    /** @return array<int, array{label: string, value: string}> */
    private function foundInATemplate(): array
    {
        $sections = 0;
        $fields = 0;

        foreach ($this->checked['template']['pages'] as $page) {
            foreach ($page['sections'] as $row) {
                $sections++;
                $fields += count($row['section']['fields']);
            }
        }

        return [
            ['label' => __('Kind of file'), 'value' => __('A whole template')],
            ['label' => __('Name it will take'), 'value' => $this->checked['template']['name']],
            ['label' => __('Structure'), 'value' => __(':pages, :sections, :fields', [
                'pages' => trans_choice(':count page|:count pages', count($this->checked['template']['pages'])),
                'sections' => trans_choice(':count section|:count sections', $sections),
                'fields' => trans_choice(':count field|:count fields', $fields),
            ])],
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    private function foundInASection(): array
    {
        $from = $this->checked['from_template'];

        return [
            ['label' => __('Kind of file'), 'value' => __('One section')],
            ['label' => __('Name it will take'), 'value' => $this->checked['section']['name']],
            ['label' => __('Structure'), 'value' => trans_choice(':count field|:count fields', count($this->checked['section']['fields']))],
            ['label' => __('Came from'), 'value' => $from === null || $from['name'] === null
                ? __('Not written down')
                : __(':name, v:version', ['name' => $from['name'], 'version' => $from['version'] ?? 1])],
        ];
    }

    private function valueListsLabel(): string
    {
        $total = count($this->checked['value_lists']);

        if ($total === 0) {
            return __('None');
        }

        $keys = array_values(array_filter(array_column($this->checked['value_lists'], 'key')));

        $reused = $keys === [] ? 0 : ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->whereIn('key', $keys)
            ->count();

        if ($reused === 0) {
            return trans_choice(':count new list|:count new lists', $total);
        }

        return __(':new, :reused already here', [
            'new' => trans_choice(':count new list|:count new lists', $total - $reused),
            'reused' => $reused,
        ]);
    }

    private function entityTypesLabel(): string
    {
        $slugs = $this->entityTypeSlugs();

        if ($slugs === []) {
            return __('None');
        }

        $mine = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->whereIn('slug', $slugs)
            ->pluck('slug')
            ->all();

        $missing = count(array_diff($slugs, $mine));

        if ($missing === 0) {
            return __('All of them already here');
        }

        return trans_choice(':count will be added|:count will be added', $missing);
    }

    /** @return list<string> */
    private function entityTypeSlugs(): array
    {
        $slugs = [];

        if ($this->checked['format'] === 'template' && $this->checked['template']['entity_type'] !== null) {
            $slugs[] = $this->checked['template']['entity_type']['slug'];
        }

        $sections = $this->checked['format'] === 'template'
            ? collect($this->checked['template']['pages'])->flatMap(fn (array $page): array => array_column($page['sections'], 'section'))->all()
            : [$this->checked['section']];

        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if ($field['relation_entity_type'] !== null) {
                    $slugs[] = $field['relation_entity_type']['slug'];
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    /** @return array<int, array{title: string, text: string}> */
    public function steps(): array
    {
        return [
            [
                'title' => __('You paste the file'),
                'text' => __('Paste it into the box below. Nothing leaves this account and nothing is uploaded anywhere.'),
            ],
            [
                'title' => __('Monica checks it'),
                'text' => __('Before anything is written, three things are checked: that the file reads as JSON, that the format is one this version understands, and that every name and every kind of field in it exists here. If something is wrong you are told which part, and nothing is created.'),
            ],
            [
                'title' => __('You use it'),
                'text' => $this->isForASection()
                    ? __('The section is added at the end of this page. Move it, rename it, change a field, then publish the version when it is right.')
                    : __('The template arrives as a draft. Read it, change what you want, publish it, then put the records you choose on it.'),
            ],
        ];
    }

    public function checkUrl(): string
    {
        return $this->isForASection()
            ? route('settings.sectionImportCheck.create', [$this->template, $this->page])
            : route('settings.templateImportCheck.create');
    }

    public function importUrl(): string
    {
        return $this->isForASection()
            ? route('settings.sectionImport.create', [$this->template, $this->page])
            : route('settings.templateImport.create');
    }

    public function backUrl(): string
    {
        return $this->isForASection()
            ? route('settings.templateBuilder.index', $this->template)
            : route('settings.templates.index');
    }

    public function example(): string
    {
        $section = [
            'slug' => 'ways-to-connect',
            'name' => 'Ways to connect',
            'fields' => [
                ['key' => 'personal_email', 'type' => 'email', 'label' => 'Personal email', 'position' => 1],
                ['key' => 'mobile', 'type' => 'phone', 'label' => 'Mobile', 'position' => 2],
            ],
        ];

        $file = ['monica' => ['format' => $this->isForASection() ? 'section' : 'template', 'format_version' => StructureFile::FORMAT_VERSION]];

        $file += $this->isForASection()
            ? ['section' => $section]
            : ['template' => [
                'slug' => 'basic-contact',
                'name' => 'Basic contact',
                'applies_to' => 'contact',
                'pages' => [[
                    'key' => 'general',
                    'label' => 'General',
                    'position' => 1,
                    'sections' => [['position' => 1, 'section' => $section]],
                ]],
            ]];

        $file['value_lists'] = [];

        return StructureFile::encode($file);
    }
}
