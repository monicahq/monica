<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Helpers\FlagState;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\Relationship;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Override;

enum TrashItemKindEnum: string implements Translatable
{
    case Contact = 'contact';

    case Entity = 'entity';

    case Group = 'group';

    case Relationship = 'relationship';

    case LifeEvent = 'life-event';

    case Reminder = 'reminder';

    case GroupMembership = 'group-membership';

    case GroupNote = 'group-note';

    case GroupFile = 'group-file';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Person',
            self::Entity => 'Record',
            self::Group => 'Group',
            self::Relationship => 'Relationship',
            self::LifeEvent => 'Life event',
            self::Reminder => 'Reminder',
            self::GroupMembership => 'Group membership',
            self::GroupNote => 'Group note',
            self::GroupFile => 'File',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Contact => 'People',
            self::Entity => 'Records',
            self::Group => 'Groups',
            self::Relationship => 'Relationships',
            self::LifeEvent => 'Life events',
            self::Reminder => 'Reminders',
            self::GroupMembership => 'Group memberships',
            self::GroupNote => 'Group notes',
            self::GroupFile => 'Files',
        };
    }

    /**
     * How many items of this kind, in a sentence counting several kinds at once.
     */
    public function countPhrase(int $count): string
    {
        return match ($this) {
            self::Contact => trans_choice(':count person|:count people', $count),
            self::Entity => trans_choice(':count record|:count records', $count),
            self::Group => trans_choice(':count group|:count groups', $count),
            self::Relationship => trans_choice(':count relationship|:count relationships', $count),
            self::LifeEvent => trans_choice(':count life event|:count life events', $count),
            self::Reminder => trans_choice(':count reminder|:count reminders', $count),
            self::GroupMembership => trans_choice(':count group membership|:count group memberships', $count),
            self::GroupNote => trans_choice(':count group note|:count group notes', $count),
            self::GroupFile => trans_choice(':count file|:count files', $count),
        };
    }

    /**
     * Several counts in one phrase, in the order of the kinds: "2 people, 3 life events and 1 file".
     *
     * @param  array<string, int>  $counts  keyed by the value of a kind
     */
    public static function countsPhrase(array $counts): string
    {
        $parts = [];

        foreach (self::cases() as $kind) {
            if (($counts[$kind->value] ?? 0) > 0) {
                $parts[] = $kind->countPhrase($counts[$kind->value]);
            }
        }

        if (count($parts) < 2) {
            return implode('', $parts);
        }

        $last = array_pop($parts);

        return __(':list and :last', ['list' => implode(', ', $parts), 'last' => $last]);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Contact => 'profile',
            self::Entity => 'pet',
            self::Group => 'family',
            self::Relationship => 'relationship',
            self::LifeEvent => 'timeline',
            self::Reminder => 'reminder',
            self::GroupMembership => 'people',
            self::GroupNote => 'note',
            self::GroupFile => 'file',
        };
    }

    /** @return class-string<Contact|Entity|Group|Relationship|LifeEvent|Reminder|GroupMember|GroupNote|GroupFile> */
    public function model(): string
    {
        return match ($this) {
            self::Contact => Contact::class,
            self::Entity => Entity::class,
            self::Group => Group::class,
            self::Relationship => Relationship::class,
            self::LifeEvent => LifeEvent::class,
            self::Reminder => Reminder::class,
            self::GroupMembership => GroupMember::class,
            self::GroupNote => GroupNote::class,
            self::GroupFile => GroupFile::class,
        };
    }

    public static function fromModel(Model $model): self
    {
        foreach (self::cases() as $kind) {
            if ($model instanceof ($kind->model())) {
                return $kind;
            }
        }

        throw new InvalidArgumentException('This record never goes to the trash.');
    }

    /**
     * Whether the account can see this kind at all. A kind behind a flag that is off for the
     * account is absent from the trash, as it is from every other screen.
     */
    public function isAvailableFor(Account $account): bool
    {
        return match ($this) {
            self::Group, self::GroupMembership, self::GroupNote => FlagState::isOn('groups', $account),
            self::GroupFile => FlagState::isOn('groups', $account) && FlagState::isOn('file-upload', $account),
            default => true,
        };
    }

    /** @return list<self> */
    public static function availableFor(Account $account): array
    {
        return array_values(array_filter(self::cases(), fn (self $kind): bool => $kind->isAvailableFor($account)));
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->pluralLabel()];
    }
}
