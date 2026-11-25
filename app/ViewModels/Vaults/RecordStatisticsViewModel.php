<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\CreateTemplateChange;
use App\Helpers\FieldCompletion;
use App\Helpers\RecordKind;
use App\Helpers\RecordStatistics;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;

class RecordStatisticsViewModel
{
    /** @var array<string, mixed>|null */
    private ?array $statistics = null;

    /** @var array<string, mixed>|null */
    private ?array $completion = null;

    public function __construct(
        private readonly User $user,
        private readonly RecordKind $kind,
        private readonly ?string $completionTemplate = null,
        private readonly ?int $completionVersion = null,
        private readonly string $completionOrder = FieldCompletion::LOWEST_FIRST,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->kind->vault;
    }

    public function title(): string
    {
        return __(':name statistics', ['name' => $this->kind->title()]);
    }

    public function scope(): string
    {
        return __(':vault vault · Non-archived :name', ['vault' => $this->kind->vault->name, 'name' => $this->kind->plural()]);
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => $this->kind->title(), 'url' => $this->kind->listUrl()],
            ['label' => __('Statistics')],
        ];
    }

    public function sidebarCurrent(): string
    {
        return $this->kind->isPeople() ? 'contacts' : "entity-type-{$this->kind->entityType->slug}";
    }

    /** @return list<array{label: string, value: int}> */
    public function overview(): array
    {
        $overview = $this->statistics()['overview'];

        $figures = [
            ['label' => $this->kind->title(), 'value' => $overview['records']],
            ['label' => __('Templates in use'), 'value' => $overview['templatesInUse']],
        ];

        if ($overview['onOlderVersions'] > 0) {
            $figures[] = ['label' => __('On older versions'), 'value' => $overview['onOlderVersions']];
        }

        if ($overview['partial'] !== null) {
            $figures[] = ['label' => __('Partial contacts'), 'value' => $overview['partial']];
        }

        $figures[] = ['label' => __('Archived :name', ['name' => $this->kind->plural()]), 'value' => $overview['archived']];

        return $figures;
    }

    public function isEmpty(): bool
    {
        return $this->statistics()['overview']['records'] === 0;
    }

    public function emptyTitle(): string
    {
        return __('No :name yet', ['name' => $this->kind->plural()]);
    }

    public function emptyDescription(): string
    {
        return __('Each :name you add is counted here by template and version.', ['name' => $this->kind->singular()]);
    }

    public function addUrl(): ?string
    {
        if ($this->kind->isPeople()) {
            return route('contacts.new', ['vault' => $this->kind->vault]);
        }

        if (! $this->user->can('addEntity', [$this->kind->entityType, $this->kind->vault])) {
            return null;
        }

        return route('entities.new', ['vault' => $this->kind->vault, 'entityType' => $this->kind->entityType]);
    }

    public function addLabel(): string
    {
        return $this->kind->isPeople() ? __('Add somebody') : __('Add :name', ['name' => $this->kind->singular()]);
    }

    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     isDefault: bool,
     *     isInactive: bool,
     *     records: int,
     *     share: int,
     *     summary: string,
     *     versions: list<array{label: string, url: string}>,
     *     viewUrl: string|null,
     *     viewLabel: string,
     *     actions: list<array{label: string, url: string}>
     * }>
     */
    public function templates(): array
    {
        $rows = $this->statistics()['templates'];

        return array_map(function (array $row) use ($rows): array {
            $template = $row['template'];
            $actions = [];
            $hasAnotherDestination = array_filter($rows, fn (array $destination): bool => $destination['template']->id !== $template->id
                && CreateTemplateChange::refusal($this->kind, $template, $destination['template']) === null) !== [];
            $hasOlderVersions = array_filter($row['versions'], fn (array $version): bool => ! $version['isPublished']) !== [];

            if ($row['records'] > 0 && $hasAnotherDestination) {
                $actions[] = ['label' => __('Change template…'), 'url' => $this->kind->route('templateChanges.new', ['from' => $template->uuid])];
            }

            if ($hasOlderVersions && CreateTemplateChange::refusal($this->kind, $template, $template) === null) {
                $actions[] = [
                    'label' => __('Update to v:number…', ['number' => $row['publishedVersion']]),
                    'url' => $this->kind->route('templateChanges.new', ['from' => $template->uuid, 'to' => $template->uuid]),
                ];
            }

            return [
                'id' => $template->uuid,
                'name' => $template->name,
                'isDefault' => $row['isDefault'],
                'isInactive' => ! $row['isActive'],
                'records' => $row['records'],
                'share' => $row['share'],
                'summary' => $row['records'] === 0
                    ? trans_choice('[0,*]:count :name', 0, ['name' => $this->kind->plural()])
                    : trans_choice(':count :singular · :share%|:count :plural · :share%', $row['records'], [
                        'singular' => $this->kind->singular(),
                        'plural' => $this->kind->plural(),
                        'share' => $row['share'],
                    ]),
                'versions' => array_map(fn (array $version): array => [
                    'label' => trans_choice('v:number · :count record|v:number · :count records', $version['records'], ['number' => $version['number']]),
                    'url' => $this->kind->listUrl(['template' => $template->uuid, 'version' => $version['number']]),
                ], $row['versions']),
                'viewUrl' => $row['records'] === 0 ? null : $this->kind->listUrl(['template' => $template->uuid]),
                'viewLabel' => trans_choice('View :count :singular|View :count :plural', $row['records'], [
                    'singular' => $this->kind->singular(),
                    'plural' => $this->kind->plural(),
                ]),
                'actions' => $actions,
            ];
        }, $rows);
    }

    public function completionUrl(): string
    {
        return $this->kind->route('statistics.index');
    }

    /** @return list<array{value: string, label: string, selected: bool}> */
    public function completionTemplates(): array
    {
        $chosen = $this->completionTemplateModel();

        return array_map(fn (array $row): array => [
            'value' => $row['template']->uuid,
            'label' => $row['template']->name,
            'selected' => $row['template']->id === $chosen?->id,
        ], $this->statistics()['templates']);
    }

    /** @return list<array{value: string, label: string, selected: bool}> */
    public function completionVersions(): array
    {
        $chosen = $this->completionTemplateModel();
        $versions = [['value' => '', 'label' => __('All versions'), 'selected' => $this->completionVersion === null]];

        foreach ($this->statistics()['templates'] as $row) {
            if ($row['template']->id !== $chosen?->id) {
                continue;
            }

            foreach ($row['versions'] as $version) {
                $versions[] = [
                    'value' => (string) $version['number'],
                    'label' => __('v:number', ['number' => $version['number']]),
                    'selected' => $this->completionVersion === $version['number'],
                ];
            }
        }

        return $versions;
    }

    /** @return list<array{value: string, label: string, selected: bool}> */
    public function completionOrders(): array
    {
        return [
            ['value' => FieldCompletion::LOWEST_FIRST, 'label' => __('Lowest completion first'), 'selected' => $this->completionOrder === FieldCompletion::LOWEST_FIRST],
            ['value' => FieldCompletion::FORM_ORDER, 'label' => __('Form order'), 'selected' => $this->completionOrder === FieldCompletion::FORM_ORDER],
        ];
    }

    /**
     * @return list<array{name: string, fields: list<array{label: string, figures: string, percentage: int|null, note: string}>}>
     */
    public function completionSections(): array
    {
        return array_map(fn (array $section): array => [
            'name' => $section['name'],
            'fields' => array_map(fn (array $field): array => [
                'label' => $field['label'],
                'figures' => $field['percentage'] === null
                    ? __('Not offered')
                    : __(':filled of :total · :percentage%', ['filled' => $field['filled'], 'total' => $field['offeredTo'], 'percentage' => $field['percentage']]),
                'percentage' => $field['percentage'],
                'note' => $field['availableFrom'] === null
                    ? __('All versions')
                    : __('Available from v:number', ['number' => $field['availableFrom']]),
            ], $section['fields']),
        ], $this->completion()['sections']);
    }

    public function completionStatus(): string
    {
        $template = $this->completionTemplateModel();

        if (! $template instanceof Template || $this->completionIsEmpty()) {
            return __('No records use this template yet.');
        }

        $fields = array_sum(array_map(fn (array $section): int => count($section['fields']), $this->completion()['sections']));

        return trans_choice('Showing :count field of :template.|Showing :count fields of :template.', $fields, ['template' => $template->name]);
    }

    public function completionIsEmpty(): bool
    {
        return $this->completion()['records'] === 0;
    }

    /** @return array<string, mixed> */
    private function statistics(): array
    {
        return $this->statistics ??= new RecordStatistics(kind: $this->kind)->toArray();
    }

    /** @return array<string, mixed> */
    private function completion(): array
    {
        $template = $this->completionTemplateModel();

        if (! $template instanceof Template) {
            return ['records' => 0, 'sections' => [], 'hiddenCount' => 0];
        }

        return $this->completion ??= new FieldCompletion(
            kind: $this->kind,
            template: $template,
            versionNumber: $this->completionVersion,
            order: $this->completionOrder,
            namesSensitiveFields: true,
        )->toArray();
    }

    private function completionTemplateModel(): ?Template
    {
        $rows = collect($this->statistics()['templates']);

        $chosen = $rows->first(fn (array $row): bool => $row['template']->uuid === $this->completionTemplate)
            ?? $rows->first(fn (array $row): bool => $row['isDefault'] && $row['records'] > 0)
            ?? $rows->first(fn (array $row): bool => $row['records'] > 0)
            ?? $rows->first();

        return $chosen['template'] ?? null;
    }
}
