<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;

class TemplateVersionHistoryViewModel
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

    public function title(): string
    {
        return __('Versions of :template', ['template' => $this->template->name]);
    }

    public function backLabel(): string
    {
        return __('Back to :template', ['template' => $this->template->name]);
    }

    public function backUrl(): string
    {
        return route('settings.templateBuilder.index', $this->template);
    }

    public function hasVersions(): bool
    {
        return $this->entries() !== [];
    }

    /** @return array<int, TemplateVersionEntry> */
    public function entries(): array
    {
        return once(function (): array {
            $versions = $this->template->versions()
                ->with([
                    'pages',
                    'assembly.sectionVersion.section',
                    'assembly.sectionVersion.fields',
                ])
                ->get();

            return $versions
                ->map(fn (TemplateVersion $version): TemplateVersionEntry => new TemplateVersionEntry(
                    version: $version,
                    previous: $version->previous_version_id === null
                        ? null
                        : $versions->firstWhere('id', $version->previous_version_id),
                ))
                ->all();
        });
    }
}
