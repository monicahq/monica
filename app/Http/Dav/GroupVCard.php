<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\ContactGroupingEnum;
use App\Models\Contact;
use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Sabre\VObject\Component\VCard;

class GroupVCard
{
    public function __construct(
        private readonly Group $group,
    ) {}

    /**
     * The card a device receives for the group, in both the RFC 6350 form and Apple's. A member the
     * address book does not hold is left out, so a device never meets a reference it cannot resolve.
     */
    public function serialize(): string
    {
        $card = new VCard([
            'VERSION' => '4.0',
            'UID' => $this->group->uuid,
            'KIND' => 'group',
            'FN' => $this->group->name,
        ]);

        $card->remove('PRODID');

        $references = $this->members()->map(fn (Contact $contact): string => self::referenceTo($contact));

        foreach ($references as $reference) {
            $card->add('MEMBER', $reference);
        }

        $card->add('X-ADDRESSBOOKSERVER-KIND', 'group');

        foreach ($references as $reference) {
            $card->add('X-ADDRESSBOOKSERVER-MEMBER', $reference);
        }

        return $card->serialize();
    }

    /**
     * The identifier the contact's own card carries: the one a device gave it when a device created
     * it, the contact's uuid otherwise. VCardMerger keeps a stored UID the same way.
     */
    public static function uidOf(Contact $contact): string
    {
        if ($contact->vcard === null) {
            return $contact->uuid;
        }

        $uid = mb_trim((string) new VCardReader($contact->vcard)->first('UID')?->getValue());

        return $uid === '' ? $contact->uuid : $uid;
    }

    public static function referenceTo(Contact $contact): string
    {
        $uid = self::uidOf($contact);

        return str_starts_with(mb_strtolower($uid), 'urn:') ? $uid : "urn:uuid:{$uid}";
    }

    /** @return Collection<int, Contact> */
    public function members(): Collection
    {
        return Contact::query()
            ->where('vault_id', $this->group->vault_id)
            ->inGrouping(ContactGroupingEnum::Everyone)
            ->whereHas('groupMembers', fn (Builder $membership): Builder => $membership
                ->where('group_id', $this->group->id)
                ->whereNull('left_on'))
            ->orderBy('id')
            ->get();
    }
}
