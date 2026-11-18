<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Models\AccountExport;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Illuminate\Database\Eloquent\Builder;
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
        return __('What :name is called, what a new contact in it starts from, how to export it, and how to delete it.', [
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

    public function deletionRefusal(): ?string
    {
        $answer = $this->user->inspect('delete', $this->vault);

        return $answer->denied() ? $answer->message() : null;
    }

    public function preferencesUrl(): string
    {
        return route('settings.preferences.index');
    }

    public function exportAction(): string
    {
        return route('settings.accountExports.create');
    }

    public function exportRefusal(): ?string
    {
        $answer = $this->user->inspect('create', AccountExport::class);

        return $answer->denied() ? $answer->message() : null;
    }

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    /** @return array<int, array{name: string, versions: array<int, array{id: int, label: string}>}> */
    public function templates(): array
    {
        return $this->contactTemplates()
            ->map(fn (Template $template): array => [
                'name' => $template->name,
                'versions' => $template->versions
                    ->map(fn (StructureVersion $version): array => [
                        'id' => $version->id,
                        'label' => $version->is($template->structure->publishedVersion)
                            ? __('Version :number (latest)', ['number' => $version->version_number])
                            : __('Version :number', ['number' => $version->version_number]),
                    ])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    public function currentStructureVersionId(): string
    {
        $current = $this->contactDefault()?->structure_version_id;

        return (string) old('structure_version_id', $current === null ? '' : (string) $current);
    }

    public function currentTemplateName(): ?string
    {
        return $this->contactDefault()?->structureVersion->structure->owner->name;
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
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->first();
    }

    /** @return Collection<int, Template> */
    private function contactTemplates(): Collection
    {
        return $this->contactTemplates ??= Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true)
            ->whereHas('structure', fn (Builder $query) => $query->where('applies_to', StructureAppliesToEnum::Contact))
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
