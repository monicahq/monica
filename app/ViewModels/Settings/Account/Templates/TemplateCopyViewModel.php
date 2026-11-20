<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Helpers\SlugBuilder;
use App\Models\Template;
use App\Models\User;

class TemplateCopyViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
    ) {}

    public function template(): Template
    {
        return $this->template;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function action(): string
    {
        return route('settings.templateCopy.create', $this->template);
    }

    public function cancelUrl(): string
    {
        return route('settings.templates.index');
    }

    /** @return array{name: string, slug: string, slugTouched: bool} */
    public function values(): array
    {
        $suggestedName = __('Copy of :name', ['name' => $this->template->name]);

        $suggestedSlug = SlugBuilder::build(
            wanted: null,
            derivedFrom: $suggestedName,
            maxLength: Template::SLUG_LENGTH,
            whenEmpty: 'template',
            isTaken: fn (string $slug): bool => Template::query()
                ->withTrashed()
                ->where('account_id', $this->user->account_id)
                ->where('slug', $slug)
                ->exists(),
        );

        $typedSlug = (string) old('slug', '');

        return [
            'name' => (string) old('name', $suggestedName),
            'slug' => (string) old('slug', $suggestedSlug),
            'slugTouched' => $typedSlug !== '',
        ];
    }
}
