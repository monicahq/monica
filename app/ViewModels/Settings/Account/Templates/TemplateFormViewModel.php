<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\TemplateAppliesToEnum;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;

/**
 * The form that names a template, and the same form that renames one. What a
 * template applies to is asked for only while it is being created: pointing
 * an existing one at a different kind of record would leave everything
 * already on it rendering something never meant for it.
 */
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
            : __('A template decides what a record looks like: which sections it carries, and which fields sit in them. You build it in the next screen.');
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

    /**
     * Where Cancel goes back to. Renaming is only reached from the builder, so
     * it returns there; creating starts on the list, so it returns to the list.
     */
    public function cancelUrl(): string
    {
        return $this->isEdit()
            ? route('settings.templateBuilder.index', $this->template)
            : route('settings.templates.index');
    }

    /**
     * What the fields start with: what the template says today, or what an
     * attempt that failed validation was carrying.
     *
     * @return array{name: string, description: string, slug: string, appliesTo: string, entityTypeId: string}
     */
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

    /**
     * Only an active type is offered, since describing something nobody can
     * file anything under is a dead end. A type already named by this
     * template stays in the list, so editing it does not silently change what
     * it applies to.
     *
     * @return array<int, array{id: int, name: string}>
     */
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

    /**
     * Whether the account has invented anything a template could describe. With
     * nothing to point at, the choice is not worth showing.
     */
    public function hasEntityTypes(): bool
    {
        return $this->entityTypes() !== [];
    }

    /**
     * The slug is settled when the template is created and never rebuilt, since
     * it travels in URLs and through the API.
     */
    public function slugIsEditable(): bool
    {
        return ! $this->isEdit();
    }

    public function slugHelp(): string
    {
        return $this->isEdit()
            ? __('Settled when the template was created, so old links keep working.')
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
