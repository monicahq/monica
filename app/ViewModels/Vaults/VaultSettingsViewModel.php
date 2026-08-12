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

/**
 * The settings of one vault: what it is called, what a contact written down
 * here starts from, and the way to be rid of the vault altogether.
 *
 * Only the contact default is offered. A vault holds a default per kind of
 * record, but the other kinds are reached from the entity type and none of
 * those screens exists, so the one default every vault is guaranteed to have is
 * the one shown.
 *
 * An account always keeps a vault, so the last one is shown as something that
 * stays rather than as a button that would be refused.
 */
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
        return __('What :name is called, what a new contact in it starts from, and how to be rid of it.', [
            'name' => $this->vault->name,
        ]);
    }

    /**
     * What the naming form starts on, which is what was typed if it came back
     * rejected and what the vault holds otherwise.
     *
     * @return array{name: string, description: string, colour: string}
     */
    public function values(): array
    {
        return [
            'name' => (string) old('name', $this->vault->name),
            'description' => (string) old('description', $this->vault->description ?? ''),
            'colour' => (string) old('colour', $this->vault->colour),
        ];
    }

    /**
     * The colours on offer, each with the name somebody using a screen reader
     * hears instead of a hex code.
     *
     * @return array<int, array{value: string, label: string}>
     */
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

    /**
     * What a contact written down in this vault could start from: every
     * published version of every contact template the account has, under the
     * template it belongs to.
     *
     * The option is a version rather than a template because that is what the
     * vault stores: a contact follows the version it was given and never "the
     * latest one". Every published version is offered rather than only the
     * newest, because which shape a vault starts its contacts on is the vault's
     * decision, and a vault is entitled to stay on an older one.
     *
     * The choice is offered even when the account has one template of one
     * version. A select holding a single option says what the vault does, and
     * that is worth more than the line of prose it replaces.
     *
     * @return array<int, array{name: string, versions: array<int, array{id: int, label: string}>}>
     */
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

    /**
     * What the select starts on, which is what was typed if the form came back
     * rejected and what the vault holds otherwise.
     */
    public function currentTemplateVersionId(): string
    {
        $current = $this->contactDefault()?->template_version_id;

        return (string) old('template_version_id', $current === null ? '' : (string) $current);
    }

    /**
     * What the vault currently starts its contacts on, said by name. A vault
     * always has one, but a vault whose default was deleted from underneath it
     * has none to name.
     */
    public function currentTemplateName(): ?string
    {
        return $this->contactDefault()?->templateVersion->template->name;
    }

    /**
     * Whether this is the only vault the account has. It goes like any other,
     * and the card that asks says what taking it leaves behind: an account
     * holding nothing, back at the screen it started on.
     */
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

    private function contactDefault(): ?VaultDefaultTemplate
    {
        return VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->first();
    }

    /**
     * The account's contact templates, each carrying the versions somebody may
     * start a contact on, newest first. A template nobody has published
     * anything for offers nothing and is left out rather than shown empty.
     *
     * @return Collection<int, Template>
     */
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
