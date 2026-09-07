<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Collection;

class VaultSettingsViewModel
{
    /** @var Collection<int, Template>|null */
    private ?Collection $contactTemplates = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Vault settings');
    }

    public function description(): string
    {
        return __('What :name is called, what a new contact in it starts from, and how to delete it.', [
            'name' => $this->vault->name,
        ]);
    }

    /** @return array{name: string, description: string, colour: string} */
    public function values(): array
    {
        return [
            'name' => (string) old('name', $this->vault->name),
            'description' => (string) old('description', $this->vault->description ?? ''),
            'colour' => (string) old('colour', $this->vault->colour),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    public function colours(): array
    {
        return array_map(
            fn (VaultColourEnum $colour): array => ['value' => $colour->value, 'label' => __($colour->label())],
            VaultColourEnum::cases(),
        );
    }

    public function updateAction(): string
    {
        return route('vaults.update', $this->vault);
    }

    public function templatesUrl(): string
    {
        return route('settings.templates.index');
    }

    public function defaultTemplateAction(): string
    {
        return route('vaults.defaultTemplate.update', $this->vault);
    }

    public function destroyAction(): string
    {
        return route('vaults.destroy', $this->vault);
    }

    /** @return array<int, array{name: string, versions: array<int, array{id: int, label: string}>}> */
    public function templates(): array
    {
        return $this->contactTemplates()
            ->map(fn (Template $template): array => [
                'name' => $template->name,
                'versions' => $template->versions
                    ->map(fn (TemplateVersion $version): array => [
                        'id' => $version->id,
                        'label' => $version->is($template->publishedVersion)
                            ? __('Version :number (latest)', ['number' => $version->version_number])
                            : __('Version :number', ['number' => $version->version_number]),
                    ])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function currentTemplateVersionId(): string
    {
        $current = $this->contactDefault()?->template_version_id;

        return (string) old('template_version_id', $current === null ? '' : (string) $current);
    }

    public function currentTemplateName(): ?string
    {
        return $this->contactDefault()?->templateVersion->template->name;
    }

    public function isTheLastVault(): bool
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->count() === 1;
    }

    public function contactCount(): int
    {
        return $this->vault->contact_count;
    }

    public function deletionConsequence(): string
    {
        if ($this->contactCount() === 0) {
            return '';
        }

        return trans_choice(
            '{1}The one contact in it is deleted too, and everything written about them.|The :count contacts in it are deleted too, and everything written about them.',
            $this->contactCount(),
        );
    }

    private function contactDefault(): ?VaultDefaultTemplate
    {
        return VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->first();
    }

    /** @return Collection<int, Template> */
    private function contactTemplates(): Collection
    {
        return $this->contactTemplates ??= Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->where('is_active', true)
            ->with([
                'publishedVersion',
                'versions' => fn ($query) => $query
                    ->where('status', VersionStatusEnum::Published)
                    ->orderByDesc('version_number'),
            ])
            ->orderBy('position')
            ->get()
            ->filter(fn (Template $template): bool => $template->versions->isNotEmpty());
    }
}
