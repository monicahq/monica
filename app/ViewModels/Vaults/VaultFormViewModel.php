<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

/**
 * The form that opens a new compartment in the account. What was typed wins
 * over what the form starts on, so a form that came back rejected shows the
 * words somebody wrote.
 *
 * An account with one contact template has nothing to choose between, so the
 * template section is left out rather than shown holding a single option, and
 * the first vault of an account is the default whatever anybody ticks.
 */
class VaultFormViewModel
{
    /** @var Collection<int, Template>|null */
    private ?Collection $contactTemplates = null;

    public function __construct(
        private readonly User $user,
    ) {}

    public function title(): string
    {
        return __('Create a vault');
    }

    public function description(): string
    {
        return __('A vault is a watertight compartment. The contacts, entities and activities you put in one stay in it, and a search in another vault never reaches them.');
    }

    public function action(): string
    {
        return route('vaults.create');
    }

    public function backUrl(): string
    {
        return route('vaults.index');
    }

    /**
     * What the fields start out holding.
     *
     * @return array{name: string, description: string, colour: string, templateVersionId: string, isDefault: bool}
     */
    public function values(): array
    {
        return [
            'name' => (string) old('name', ''),
            'description' => (string) old('description', ''),
            'colour' => (string) old('colour', Vault::DEFAULT_COLOUR),
            'templateVersionId' => (string) old('template_version_id', $this->suppliedTemplateVersionId()),
            'isDefault' => $this->isDefault(),
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

    /**
     * The account's contact templates that are still offered and have
     * something published to point at. The option is the published version
     * rather than the template, because that is what the vault stores.
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
     * Whether the screen asks which template a contact starts from. One
     * template is not a choice, and a select holding a single option only
     * invites somebody to wonder what else there was.
     */
    public function showsTemplates(): bool
    {
        return count($this->templates()) > 1;
    }

    /**
     * Whether this is the first vault of the account, which is the default
     * whatever anybody ticks: something created without a vault chosen has
     * nowhere else to land.
     */
    public function isFirstVault(): bool
    {
        return ! Vault::query()->where('account_id', $this->user->account_id)->exists();
    }

    /**
     * What the current default is called, which is what the sentence under the
     * checkbox is about.
     */
    public function currentDefaultName(): ?string
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_default', true)
            ->value('name');
    }

    /**
     * What ticking the box costs, said plainly. The first vault has no choice
     * to explain; every one after it takes the title off the vault holding it.
     */
    public function defaultHintWhenTicked(): string
    {
        if ($this->isFirstVault()) {
            return __('This is your first vault, so it is the default until you make another one.');
        }

        return __(':name stops being the default the moment this vault is created.', [
            'name' => $this->currentDefaultName() ?? __('Your current default vault'),
        ]);
    }

    public function defaultHintWhenLeft(): string
    {
        return __(':name stays the default.', [
            'name' => $this->currentDefaultName() ?? __('Your current default vault'),
        ]);
    }

    /**
     * The version the supplied contact template publishes, which is what a new
     * vault starts its contacts on unless somebody says otherwise.
     */
    private function suppliedTemplateVersionId(): string
    {
        $version = $this->contactTemplates()
            ->sortByDesc(fn (Template $template): bool => $template->is_system)
            ->first()?->published_version_id;

        return $version === null ? '' : (string) $version;
    }

    /**
     * Whether the checkbox starts ticked. A checkbox nobody ticked sends
     * nothing at all, so whether there is any old input to speak of is what
     * tells a cleared box from a form nobody has submitted yet.
     */
    private function isDefault(): bool
    {
        if ($this->isFirstVault()) {
            return true;
        }

        if (session()->hasOldInput()) {
            return session()->hasOldInput('is_default');
        }

        return false;
    }

    /**
     * The templates read once and kept, since the screen asks three questions
     * of the same list: what to offer, whether to offer anything at all, and
     * which one to start on.
     *
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

    public function user(): User
    {
        return $this->user;
    }
}
