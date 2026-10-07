<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\PreviewContactMerge;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactMergeViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?Contact $other = null,
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

    public function other(): ?Contact
    {
        return $this->other;
    }

    public function title(): string
    {
        if ($this->other === null) {
            return __('Merge somebody into :name', ['name' => $this->contact->name]);
        }

        return __('Merge :other into :name', ['other' => $this->other->name, 'name' => $this->contact->name]);
    }

    public function intro(): string
    {
        return __('Two records for one person become one. :name stays, and everything the other held comes over.', ['name' => $this->contact->name]);
    }

    public function warning(): string
    {
        return __('The other contact stops appearing in this vault. Nothing is thrown away, and you can undo this.');
    }

    public function emptyLine(): string
    {
        return __('There is nobody else in this vault to merge with yet.');
    }

    /** @return list<array{uuid: string, name: string, url: string}> */
    public function candidates(): array
    {
        return $this->vault->contacts()
            ->whereKeyNot($this->contact->id)
            ->where('is_me', false)
            ->orderBy('sort_key')
            ->get()
            ->map(fn (Contact $contact): array => [
                'uuid' => $contact->uuid,
                'name' => $contact->name,
                'url' => route('contacts.merge.show', [
                    'vault' => $this->vault,
                    'contact' => $this->contact,
                    'other' => $contact,
                ]),
            ])
            ->all();
    }

    /** @return list<array{name: string, avatarUrl: string|null, url: string}> */
    public function records(): array
    {
        if (! $this->other instanceof Contact) {
            return [];
        }

        return [
            $this->record($this->contact),
            $this->record($this->other),
        ];
    }

    /** @return array<string, mixed> */
    public function preview(): array
    {
        if (! $this->other instanceof Contact) {
            return [];
        }

        return once(fn (): array => new PreviewContactMerge(
            user: $this->user,
            kept: $this->contact,
            absorbed: $this->other,
        )->execute());
    }

    /** @param array{kind: string, count: int} $move */
    public function movedLine(array $move): string
    {
        return match ($move['kind']) {
            'name_representation' => trans_choice(':count way of writing the name comes over|:count ways of writing the name come over', $move['count']),
            'field_value' => trans_choice(':count answer comes over|:count answers come over', $move['count']),
            'field_value_row' => trans_choice(':count entry comes over|:count entries come over', $move['count']),
            'reminder' => trans_choice(':count reminder comes over|:count reminders come over', $move['count']),
            'share_link' => trans_choice(':count shared link keeps working|:count shared links keep working', $move['count']),
            'relation_pointer' => trans_choice(':count other record points here instead|:count other records point here instead', $move['count']),
            default => '',
        };
    }

    public function confirmUrl(): string
    {
        return route('contacts.merge.create', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'other' => $this->other,
        ]);
    }

    public function backUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function pickerUrl(): string
    {
        return route('contacts.merge.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /** @return array{name: string, avatarUrl: string|null, url: string} */
    private function record(Contact $contact): array
    {
        return [
            'name' => $contact->name,
            'avatarUrl' => $contact->avatar === null
                ? null
                : route('files.show', ['vault' => $this->vault, 'file' => $contact->avatar]),
            'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
        ];
    }
}
