<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\TemplateChangeOutcomeEnum;
use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TemplateFilter
{
    public function __construct(
        public readonly ?Template $template = null,
        public readonly ?int $versionNumber = null,
        public readonly ?TemplateChange $templateChange = null,
    ) {}

    public static function fromValidated(RecordKind $kind, ?string $template, ?int $versionNumber, ?string $change = null): self
    {
        $templateChange = $change === null ? null : TemplateChange::query()
            ->where('vault_id', $kind->vault->id)
            ->where('entity_type_id', $kind->entityType?->id)
            ->where('uuid', $change)
            ->first();
        $chosen = $template === null ? null : $kind->templates()->firstWhere('uuid', $template);

        if (! $chosen instanceof Template) {
            return new self(templateChange: $templateChange);
        }

        return new self(template: $chosen, versionNumber: $versionNumber, templateChange: $templateChange);
    }

    public function isActive(): bool
    {
        return $this->template instanceof Template || $this->templateChange instanceof TemplateChange;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, string $column = 'structure_version_id'): Builder
    {
        if ($this->templateChange instanceof TemplateChange) {
            $query->whereIn($query->getModel()->getQualifiedKeyName(), TemplateChangeItem::query()
                ->where('template_change_id', $this->templateChange->id)
                ->where('outcome', TemplateChangeOutcomeEnum::Moved)
                ->select('record_id'));
        }

        if (! $this->template instanceof Template) {
            return $query;
        }

        return $query->whereIn($column, $this->template->structure->versions()
            ->when($this->versionNumber !== null, fn (Builder $versions) => $versions->where('version_number', $this->versionNumber))
            ->select('id'));
    }

    /** @return array{template: string|null, version: int|null, change: string|null} */
    public function parameters(): array
    {
        return [
            'template' => $this->template?->uuid,
            'version' => $this->template instanceof Template ? $this->versionNumber : null,
            'change' => $this->templateChange?->uuid,
        ];
    }

    /** @return list<array{label: string, parameters: array{template: string|null, version: int|null, change: string|null}}> */
    public function chips(): array
    {
        $chips = [];
        $change = $this->templateChange?->uuid;

        if ($this->templateChange instanceof TemplateChange) {
            $chips[] = [
                'label' => __('Moved by a change of template'),
                'parameters' => [...$this->parameters(), 'change' => null],
            ];
        }

        if (! $this->template instanceof Template) {
            return $chips;
        }

        $chips[] = [
            'label' => __('Template: :name', ['name' => $this->template->name]),
            'parameters' => ['template' => null, 'version' => null, 'change' => $change],
        ];

        if ($this->versionNumber !== null) {
            $chips[] = [
                'label' => __('Version: v:number', ['number' => $this->versionNumber]),
                'parameters' => ['template' => $this->template->uuid, 'version' => null, 'change' => $change],
            ];
        }

        return $chips;
    }
}
