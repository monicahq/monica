<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TrashItemKindEnum;
use App\Exceptions\CannotRestoreFromTheTrash;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Bring one item back from the vault's trash, whatever its kind, along with what went to the trash with
 * it. An item that went with a person or a group still in the trash waits for them, and says so.
 */
class RestoreTrashItem
{
    /** @var array<string, int> */
    private array $broughtBack = [];

    private TrashItem $item;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly TrashItemKindEnum $kind,
        private readonly string $uuid,
    ) {}

    public function execute(): TrashItem
    {
        $this->validate();
        $this->restore();

        return $this->item;
    }

    /**
     * How many items of each kind came back with the item, keyed by the value of the kind.
     *
     * @return array<string, int>
     */
    public function broughtBack(): array
    {
        return $this->broughtBack;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $trash = new VaultTrash(vault: $this->vault);
        $this->item = $trash->find($this->kind, $this->uuid);

        $obstacle = $this->item->obstacle();

        if ($obstacle !== null) {
            throw new CannotRestoreFromTheTrash($obstacle);
        }

        $this->broughtBack = $trash->dependentCounts([$this->item])["{$this->item->record->getMorphClass()}:{$this->item->record->getKey()}"] ?? [];
    }

    private function restore(): void
    {
        $record = $this->item->record;

        match (true) {
            $record instanceof Contact => new RestoreContact(user: $this->user, vault: $this->vault, contact: $record)->execute(),
            $record instanceof Entity => new RestoreEntity(user: $this->user, vault: $this->vault, entity: $record)->execute(),
            $record instanceof Group => new RestoreGroup(user: $this->user, vault: $this->vault, group: $record)->execute(),
            $record instanceof Relationship => new RestoreRelationship(user: $this->user, vault: $this->vault, relationship: $record)->execute(),
            $record instanceof LifeEvent => new RestoreLifeEvent(user: $this->user, vault: $this->vault, contact: $this->someoneIn($record), lifeEvent: $record)->execute(),
            $record instanceof Reminder => new RestoreReminder(user: $this->user, vault: $this->vault, reminder: $record)->execute(),
            $record instanceof GroupMember => new RestoreGroupMembership(user: $this->user, vault: $this->vault, membership: $record)->execute(),
            $record instanceof GroupNote => new RestoreGroupNote(user: $this->user, vault: $this->vault, note: $record)->execute(),
            $record instanceof GroupFile => new RestoreGroupFile(user: $this->user, vault: $this->vault, attachment: $record)->execute(),
        };
    }

    private function someoneIn(LifeEvent $lifeEvent): Contact
    {
        $participant = LifeEventParticipant::query()
            ->where('life_event_id', $lifeEvent->id)
            ->where('participant_type', new Contact()->getMorphClass())
            ->orderBy('position')
            ->firstOrFail();

        return Contact::query()->withTrashed()->findOrFail($participant->participant_id);
    }
}
