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

/**
 * The form that writes somebody down.
 *
 * It asks for a name and nothing else. Everything the contact page eventually
 * holds belongs to the template, and is filled in on the contact once it
 * exists, so a form that asked for all of it here would be asking twice.
 *
 * What was typed wins over what the form starts on, so a form that came back
 * rejected shows the words somebody wrote rather than quietly reverting them.
 *
 * As on the vault form, an account with one contact template has nothing to
 * choose between, and the select is left out rather than shown holding a single
 * option. What the template is called is the vault's business and is said
 * there; a form that repeated it would be answering a question nobody standing
 * here is asking.
 *
 * The screen says nothing else about itself. It speaks only when it cannot do
 * its job, which is when the vault has nothing for a contact to start from.
 */
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
        return __('A name is enough to start. Everything else belongs to the template, and you fill it in on the contact itself once it exists.');
    }

    public function action(): string
    {
        return route('contacts.create', $this->vault);
    }

    /**
     * The list this form adds to, which is the step above it in the trail and
     * the one place both the bar and Cancel lead. Two ways out of a form that
     * land on different screens is a small trap.
     */
    public function contactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * What the fields start out holding.
     *
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

    /**
     * The templates this contact could start from: the account's contact
     * templates that are still offered and have something published to point at.
     *
     * The option is the published version rather than the template, because that
     * is what the contact stores. A contact follows the version it was given and
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
     * Whether the screen asks which template the contact starts from. One
     * template is not a choice, and a select holding a single option only
     * invites somebody to wonder what else there was.
     */
    public function showsTemplates(): bool
    {
        return count($this->templates()) > 1;
    }

    /**
     * The orders a name can be written and sorted in. The empty one comes first
     * and is what a contact is created on unless somebody says otherwise: a name
     * follows the reader's own preference until the contact needs its own, which
     * is what the nullable column means.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function nameOrders(): array
    {
        $orders = [['value' => '', 'label' => __('Follow your own preference')]];

        foreach (NameOrderEnum::cases() as $order) {
            $orders[] = ['value' => $order->value, 'label' => __($order->label())];
        }

        return $orders;
    }

    /**
     * The two things somebody can say about the contact while writing it down.
     *
     * Being the contact that represents you is not offered here. A vault holds
     * one, it is refused a second, and turning an ordinary new contact into it
     * by ticking a box is not what somebody opening this screen came to do.
     *
     * @return array<int, array{id: string, name: string, help: string, checked: bool}>
     */
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
                'help' => __('Somebody you only know through another person. They stay out of the lists until you fill them in, and become an ordinary contact the moment you do.'),
                'checked' => $values['isPartial'],
            ],
        ];
    }

    /**
     * What stands between this form and a contact, when anything does. Nothing
     * is said when the form works: a screen that explains itself while it is
     * behaving is a screen somebody stops reading.
     */
    public function blocker(): ?string
    {
        if ($this->canCreate()) {
            return null;
        }

        return __('This vault has no template for new contacts to start from. Give it one in the vault settings, and this form will work.');
    }

    /**
     * Whether the form can be submitted at all. A vault with no contact default
     * and an account with no published contact template are the same problem
     * seen from two ends, and either of them makes the button a lie.
     */
    public function canCreate(): bool
    {
        return $this->startingVersion() !== null;
    }

    /**
     * The version the contact would land on as the form stands: whichever the
     * select is showing, or the vault's default when there is no select.
     *
     * The account is checked even though the id came from a select the screen
     * itself drew, since what comes back from a rejected form is whatever was
     * posted, and a version of somebody else's is not one to start from.
     */
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

    /**
     * The version the vault starts its contacts on, which is what the select
     * opens on when there is one.
     */
    private function defaultTemplateVersionId(): string
    {
        $version = VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->value('template_version_id');

        return $version === null ? '' : (string) $version;
    }

    /**
     * Whether a checkbox starts ticked. A checkbox nobody ticked sends nothing
     * at all, so whether there is any old input to speak of is what tells a
     * cleared box from a form nobody has submitted yet.
     */
    private function ticked(string $field): bool
    {
        if (! session()->hasOldInput()) {
            return false;
        }

        return session()->hasOldInput($field);
    }

    /**
     * The templates read once and kept, since the screen asks two questions of
     * the same list: what to offer, and whether to offer anything at all.
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
}
