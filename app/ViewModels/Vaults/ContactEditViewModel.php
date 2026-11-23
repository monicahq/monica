<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;

class ContactEditViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return __('Edit contact');
    }

    public function description(): string
    {
        return __('What the template does not decide about :name.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function template(): ?Template
    {
        $owner = $this->contact->structureVersion->structure->owner;

        return $owner instanceof Template ? $owner : null;
    }

    public function templateUrl(): ?string
    {
        $template = $this->template();

        if (! $template instanceof Template) {
            return null;
        }

        return route('settings.templateBuilder.index', $template);
    }

    public function action(): string
    {
        return route('contacts.name.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function avatarDescription(): string
    {
        return __('A square photo reads best. JPEG, PNG or WebP, up to 5 MB. Without one, :name keeps their initials on a coloured background.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function hasAvatar(): bool
    {
        return $this->contact->hasAvatar();
    }

    public function avatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->vault, 'file' => $this->contact->avatar]);
    }

    public function uploadsAreOn(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
    }

    public function updateAvatarUrl(): string
    {
        return route('contacts.avatar.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyAvatarUrl(): string
    {
        return route('contacts.avatar.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyUrl(): string
    {
        return route('contacts.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function mayBeDeleted(): bool
    {
        return $this->user->can('delete', $this->contact);
    }

    public function deleteDescription(): string
    {
        $response = $this->user->inspect('delete', $this->contact);

        return $response->denied()
            ? (string) $response->message()
            : __('Taking them off the list can be undone.');
    }

    /** @return array{title: string, lead: string, lines: list<array{icon: string, text: string}>, url: string, confirmTitle: string, confirmBody: string, reachabilityLine: string|null, refusal: string|null}|null */
    public function demotion(): ?array
    {
        if ($this->contact->is_partial) {
            return null;
        }

        $name = $this->contact->name;
        $answer = $this->user->inspect('demote', $this->contact);
        $isReached = Relationship::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('contact_a_id', $this->contact->id)
                ->orWhere('contact_b_id', $this->contact->id))
            ->exists();

        return [
            'title' => __('Keep :name as a partial contact', ['name' => $name]),
            'lead' => __('For somebody you only need to know through another person, like a colleague’s partner or a friend’s child.'),
            'lines' => [
                ['icon' => 'people', 'text' => __(':name leaves your list of people and no longer counts towards your plan.', ['name' => $name])],
                ['icon' => 'check', 'text' => __('Everything written about :name stays, and so do their relationships.', ['name' => $name])],
                ['icon' => 'arrow-right', 'text' => __('You can make :name a full contact again at any time.', ['name' => $name])],
            ],
            'url' => route('contacts.partial.update', ['vault' => $this->vault, 'contact' => $this->contact]),
            'confirmTitle' => __('Make :name a partial contact?', ['name' => $name]),
            'confirmBody' => __(':name leaves the list of people and the address books synchronised to your phones, and their birthday leaves your birthday calendar. Everything written about them stays, and they can still be found in search.', ['name' => $name]),
            'reachabilityLine' => $isReached
                ? null
                : __(':name has no relationship, so you will find them only under Partial contacts in the list of people, or in search.', ['name' => $name]),
            'refusal' => $answer->allowed() ? null : $answer->message(),
        ];
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function nameEditor(): ContactNameEditorBlock
    {
        return new ContactNameEditorBlock(contact: $this->contact);
    }

    public function shortName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }

    public function value(string $field): ?string
    {
        return old($field, $this->contact->$field);
    }
}
