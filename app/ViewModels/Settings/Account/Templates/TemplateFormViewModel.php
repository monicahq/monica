<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\TemplateAppliesToEnum;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;

class TemplateFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Template $template = null,
    ) {}

    public function isEdit(): bool
    {
        return $this->template !== null;
    }

    public function title(): string
    {
        return $this->isEdit() ? __('Edit the template') : __('Create a template');
    }

    public function description(): string
    {
        return $this->isEdit()
            ? __('The name and the description are what the picker shows. What the template holds is changed in the builder.')
            : __('A template decides what a record shows: which sections it carries, and which fields are in them. You build it in the next screen.');
    }

    public function saveLabel(): string
    {
        return $this->isEdit() ? __('Save the template') : __('Create the template');
    }

    public function action(): string
    {
        return $this->isEdit()
            ? route('settings.templates.update', $this->template)
            : route('settings.templates.create');
    }

    public function method(): string
    {
        return $this->isEdit() ? 'put' : 'post';
    }

    public function cancelUrl(): string
    {
        return $this->isEdit()
            ? route('settings.templateBuilder.index', $this->template)
            : route('settings.templates.index');
    }

    /** @return array{name: string, description: string, slug: string, appliesTo: string, entityTypeId: string} */
    public function values(): array
    {
        return [
            'name' => (string) old('name', $this->template->name ?? ''),
            'description' => (string) old('description', $this->template->description ?? ''),
            'slug' => (string) old('slug', $this->template->slug ?? ''),
            'appliesTo' => (string) old('applies_to', $this->template?->applies_to->value ?? TemplateAppliesToEnum::Contact->value),
            'entityTypeId' => (string) old('entity_type_id', $this->template->entity_type_id ?? ''),
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    public function entityTypes(): array
    {
        return EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->template?->entity_type_id))
            ->orderBy('position')
            ->get()
            ->map(fn (EntityType $type): array => ['id' => $type->id, 'name' => $type->name_plural])
            ->all();
    }

    public function hasEntityTypes(): bool
    {
        return $this->entityTypes() !== [];
    }

    public function slugIsEditable(): bool
    {
        return ! $this->isEdit();
    }

    public function slugHelp(): string
    {
        return $this->isEdit()
            ? __('Fixed when the template was created, so old links keep working.')
            : __('Left empty, it is made from the name. Lowercase letters, numbers and dashes.');
    }

    public function template(): ?Template
    {
        return $this->template;
    }

    public function user(): User
    {
        return $this->user;
    }
}
