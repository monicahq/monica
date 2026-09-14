<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

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
        return __('A vault holds one part of your life. The contacts, records and activities you put in one stay in it, and a search in another vault never reaches them.');
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
     * What the free offer allows, said only when the account is at it. The form still opens and
     * still edits: it is an addition that is refused, never what is already there.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Vault::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    /** @return array{name: string, description: string, colour: string, templateVersionId: string, isDefault: bool} */
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

    /** @return array<int, array{value: string, label: string}> */
    public function colours(): array
    {
        return array_map(
            fn (VaultColourEnum $colour): array => ['value' => $colour->value, 'label' => __($colour->label())],
            VaultColourEnum::cases(),
        );
    }

    /** @return array<int, array{id: int, name: string}> */
    public function templates(): array
    {
        return $this->contactTemplates()
            ->map(fn (Template $template): array => [
                'id' => (int) $template->published_version_id,
                'name' => $template->name,
            ])
            ->all();
    }

    public function showsTemplates(): bool
    {
        return count($this->templates()) > 1;
    }

    public function isFirstVault(): bool
    {
        return ! Vault::query()->where('account_id', $this->user->account_id)->exists();
    }

    public function currentDefaultName(): ?string
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_default', true)
            ->value('name');
    }

    public function defaultHintWhenTicked(): string
    {
        if ($this->isFirstVault()) {
            return __('This is your first vault, so it is the default until you make another one.');
        }

        return __(':name stops being the default when this vault is created.', [
            'name' => $this->currentDefaultName() ?? __('Your current default vault'),
        ]);
    }

    public function defaultHintWhenLeft(): string
    {
        return __(':name stays the default.', [
            'name' => $this->currentDefaultName() ?? __('Your current default vault'),
        ]);
    }

    private function suppliedTemplateVersionId(): string
    {
        $version = $this->contactTemplates()
            ->sortByDesc(fn (Template $template): bool => $template->is_system)
            ->first()?->published_version_id;

        return $version === null ? '' : (string) $version;
    }

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

    /** @return Collection<int, Template> */
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
