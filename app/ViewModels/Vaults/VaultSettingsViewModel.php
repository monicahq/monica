<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Models\Template;
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
     * The templates a contact written down in this vault could start from. The
     * option is the published version rather than the template, because that is
     * what the vault stores: a contact follows the version it was given and
     * never "the latest one".
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function templates(): array
    {
        return $this->contactTemplates()
            ->map(fn (Template $template): array => [
                'id' => (int) $template->published_version_id,
                'name' => $template->name,
            ])
            ->all();
    }

    /**
     * Whether there is anything to choose between. One template is not a
     * choice, and a select holding a single option only invites somebody to
     * wonder what else there was.
     */
    public function hasTemplateChoice(): bool
    {
        return count($this->templates()) > 1;
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
     * @return Collection<int, Template>
     */
    private function contactTemplates(): Collection
    {
        return $this->contactTemplates ??= Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->where('is_active', true)
            ->whereNotNull('published_version_id')
            ->orderBy('position')
            ->get();
    }
}
