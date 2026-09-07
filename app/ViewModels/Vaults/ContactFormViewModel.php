<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Collection;

class ContactFormViewModel
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
        return __('New contact');
    }

    public function description(): string
    {
        return __('A name is enough to start. Everything else belongs to the template. You fill it in on the contact once it exists.');
    }

    public function action(): string
    {
        return route('contacts.create', $this->vault);
    }

    public function contactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * @return array{prefix: string, firstName: string, middleName: string, lastName: string, suffix: string, nickname: string, maidenName: string, nameOrder: string, templateVersionId: string, isFavourite: bool, isPartial: bool, keepGoing: bool}
     */
    public function values(): array
    {
        return [
            'prefix' => (string) old('prefix', ''),
            'firstName' => (string) old('first_name', ''),
            'middleName' => (string) old('middle_name', ''),
            'lastName' => (string) old('last_name', ''),
            'suffix' => (string) old('suffix', ''),
            'nickname' => (string) old('nickname', ''),
            'maidenName' => (string) old('maiden_name', ''),
            'nameOrder' => (string) old('name_order', ''),
            'templateVersionId' => (string) old('template_version_id', $this->defaultTemplateVersionId()),
            'isFavourite' => $this->ticked('is_favourite'),
            'isPartial' => $this->ticked('is_partial'),
            'keepGoing' => $this->ticked('keep_going'),
        ];
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

    /** @return array<int, array{value: string, label: string}> */
    public function nameOrders(): array
    {
        $orders = [['value' => '', 'label' => __('Follow your own preference')]];

        foreach (NameOrderEnum::cases() as $order) {
            $orders[] = ['value' => $order->value, 'label' => __($order->label())];
        }

        return $orders;
    }

    /** @return array<int, array{id: string, name: string, help: string, checked: bool}> */
    public function flags(): array
    {
        $values = $this->values();

        return [
            [
                'id' => 'is_favourite',
                'name' => __('Favourite'),
                'help' => __('Kept at the top of the contact list.'),
                'checked' => $values['isFavourite'],
            ],
            [
                'id' => 'is_partial',
                'name' => __('Partial contact'),
                'help' => __('Somebody you only know through another person. They stay off the lists until you fill them in, and become an ordinary contact as soon as you do.'),
                'checked' => $values['isPartial'],
            ],
        ];
    }

    public function blocker(): ?string
    {
        if ($this->canCreate()) {
            return null;
        }

        return __('This vault has no template for new contacts to start from. Give it one in the vault settings, and this form will work.');
    }

    public function canCreate(): bool
    {
        return $this->startingVersion() !== null;
    }

    private function startingVersion(): ?TemplateVersion
    {
        $chosen = (int) $this->values()['templateVersionId'];

        if ($chosen === 0) {
            return null;
        }

        return TemplateVersion::query()
            ->where('account_id', $this->user->account_id)
            ->find($chosen);
    }

    private function defaultTemplateVersionId(): string
    {
        $version = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->value('template_version_id');

        return $version === null ? '' : (string) $version;
    }

    private function ticked(string $field): bool
    {
        if (! session()->hasOldInput()) {
            return false;
        }

        return session()->hasOldInput($field);
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
}
