<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\LifeEventSourceEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\Duration;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class LifeEventDetailsViewModel
{
    private const array FILE_TYPES = [FieldTypeEnum::File, FieldTypeEnum::Image, FieldTypeEnum::Avatar];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly LifeEvent $lifeEvent,
    ) {}

    public function panelId(): string
    {
        return "life-event-details-{$this->lifeEvent->uuid}";
    }

    public function title(): string
    {
        return $this->lifeEvent->title ?? $this->loaded()->lifeEventType->name;
    }

    public function typeName(): string
    {
        return $this->loaded()->lifeEventType->name;
    }

    public function categoryName(): ?string
    {
        return $this->loaded()->lifeEventType->lifeEventCategory?->name;
    }

    public function icon(): string
    {
        $icon = $this->loaded()->lifeEventType->icon;

        return in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON;
    }

    public function tint(): string
    {
        return VaultColourEnum::tintFor($this->loaded()->lifeEventType->colour);
    }

    public function narrative(): ?string
    {
        return $this->lifeEvent->narrative;
    }

    /** @return list<array{label: string, value: string, url: string|null}> */
    public function facts(): array
    {
        $lifeEvent = $this->loaded();
        $facts = [['label' => __('Date'), 'value' => $this->date(), 'url' => null]];

        if ($lifeEvent->happened_at_time !== null) {
            $time = $this->user->time_format->format(CarbonImmutable::parse($lifeEvent->happened_at_time));
            $differs = $lifeEvent->timezone !== null && $lifeEvent->timezone !== $this->user->timezone;

            $facts[] = [
                'label' => __('Time'),
                'value' => $differs ? __(':time, :timezone', ['time' => $time, 'timezone' => $lifeEvent->timezone]) : $time,
                'url' => null,
            ];
        }

        if ($lifeEvent->duration_in_minutes !== null) {
            $facts[] = ['label' => __('Duration'), 'value' => Duration::wording($lifeEvent->duration_in_minutes), 'url' => null];
        }

        if ($lifeEvent->location !== null) {
            $facts[] = ['label' => __('Place'), 'value' => $lifeEvent->location, 'url' => null];
        }

        if ($lifeEvent->group !== null && ! $lifeEvent->group->trashed()) {
            $facts[] = [
                'label' => __('Group'),
                'value' => $lifeEvent->group->name,
                'url' => route('groups.show', ['vault' => $this->vault, 'group' => $lifeEvent->group]),
            ];
        }

        $formatter = new FieldValueFormatter($this->user);

        foreach ($this->fieldValues()->reject(fn (FieldValue $value): bool => in_array($value->field->type, self::FILE_TYPES, true)) as $value) {
            $written = $formatter->format($value);

            if ($written !== null) {
                $facts[] = ['label' => $value->field->label, 'value' => $written, 'url' => null];
            }
        }

        return $facts;
    }

    /** @return list<array{name: string, url: string|null, avatarUrl: string|null, relation: string|null, role: string|null, notes: string|null}> */
    public function people(): array
    {
        $relations = $this->relationsToMe();

        return $this->loaded()->participants
            ->filter(fn (LifeEventParticipant $participation): bool => $participation->participant !== null)
            ->map(function (LifeEventParticipant $participation) use ($relations): array {
                $person = $participation->participant;
                $relation = match (true) {
                    ! $person instanceof Contact => $person->entityType->name_singular,
                    $person->is_me => __('You'),
                    default => $relations->get($person->id),
                };

                return [
                    'name' => $person->name,
                    'url' => $this->urlOf($person),
                    'avatarUrl' => $person instanceof Contact && $person->avatar !== null
                        ? route('files.variants.show', ['vault' => $this->vault, 'file' => $person->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS])
                        : null,
                    'relation' => $relation,
                    'role' => $participation->role,
                    'notes' => $participation->notes,
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array{name: string, size: string, url: string}> */
    public function files(): array
    {
        return $this->fieldValues()
            ->filter(fn (FieldValue $value): bool => in_array($value->field->type, self::FILE_TYPES, true) && $value->file !== null)
            ->map(fn (FieldValue $value): array => [
                'name' => $value->file->original_name,
                'size' => Number::fileSize($value->file->size_in_bytes, precision: 1),
                'url' => route('files.show', ['vault' => $this->vault, 'file' => $value->file]),
            ])
            ->values()
            ->all();
    }

    public function added(): string
    {
        $date = DateFormatEnum::write($this->lifeEvent->created_at->setTimezone($this->user->timezone), $this->dateFormat());

        return match ($this->lifeEvent->created_from) {
            LifeEventSourceEnum::Contact => __('Added on :date from the page of a person.', ['date' => $date]),
            LifeEventSourceEnum::Group => __('Added on :date from the page of a group.', ['date' => $date]),
            LifeEventSourceEnum::Dashboard => __('Added on :date from the dashboard.', ['date' => $date]),
            LifeEventSourceEnum::Day => __('Added on :date from the page of a day.', ['date' => $date]),
            LifeEventSourceEnum::Api => __('Added on :date through the API.', ['date' => $date]),
            LifeEventSourceEnum::Mcp => __('Added on :date by an assistant.', ['date' => $date]),
            LifeEventSourceEnum::Import => __('Added on :date by an import.', ['date' => $date]),
            LifeEventSourceEnum::Module => __('Added on :date by a module.', ['date' => $date]),
        };
    }

    public function changed(): ?string
    {
        $updatedAt = $this->lifeEvent->updated_at;

        if ($updatedAt === null || $updatedAt->equalTo($this->lifeEvent->created_at)) {
            return null;
        }

        return __('Last changed :when.', ['when' => $updatedAt->diffForHumans()]);
    }

    private function date(): string
    {
        $lifeEvent = $this->lifeEvent;

        if ($lifeEvent->date_precision === DatePrecisionEnum::Unknown) {
            return __('Date unknown');
        }

        $period = new GroupPeriod($this->user);

        if ($lifeEvent->ended_on === null) {
            return (string) $period->date($lifeEvent->happened_on, $lifeEvent->date_precision);
        }

        return (string) $period->between($lifeEvent->happened_on, $lifeEvent->date_precision, $lifeEvent->ended_on, $lifeEvent->date_precision);
    }

    private function urlOf(Contact|Entity $person): ?string
    {
        if ($person instanceof Entity) {
            return route('entities.index', ['vault' => $this->vault, 'entityType' => $person->entityType]);
        }

        return $person->is_partial ? null : route('contacts.show', ['vault' => $this->vault, 'contact' => $person]);
    }

    /**
     * How each contact who was there is related to the vault's own person, read from that person's
     * side of the link: the far side carries the word, as on a contact's relationships.
     *
     * @return Collection<int, string>
     */
    private function relationsToMe(): Collection
    {
        $ids = $this->loaded()->participants
            ->filter(fn (LifeEventParticipant $participation): bool => $participation->participant instanceof Contact)
            ->map(fn (LifeEventParticipant $participation): int => $participation->participant_id)
            ->values()
            ->all();

        $me = Contact::query()->where('vault_id', $this->vault->id)->where('is_me', true)->first();

        if ($me === null || $ids === []) {
            return collect();
        }

        return Relationship::query()
            ->where('vault_id', $this->vault->id)
            ->where(fn ($query) => $query
                ->where(fn ($side) => $side->where('contact_a_id', $me->id)->whereIn('contact_b_id', $ids))
                ->orWhere(fn ($side) => $side->where('contact_b_id', $me->id)->whereIn('contact_a_id', $ids)))
            ->with('relationshipType')
            ->get()
            ->mapWithKeys(fn (Relationship $relationship): array => $relationship->contact_a_id === $me->id
                ? [$relationship->contact_b_id => $relationship->relationshipType->role_b]
                : [$relationship->contact_a_id => $relationship->relationshipType->role_a]);
    }

    /** @return Collection<int, FieldValue> */
    private function fieldValues(): Collection
    {
        return $this->loaded()->fieldValues
            ->sortBy([
                fn (FieldValue $a, FieldValue $b): int => $a->field->position <=> $b->field->position,
                fn (FieldValue $a, FieldValue $b): int => ($a->field_value_row_id ?? 0) <=> ($b->field_value_row_id ?? 0),
            ])
            ->values();
    }

    private function loaded(): LifeEvent
    {
        return once(fn (): LifeEvent => $this->lifeEvent->load([
            'lifeEventType.lifeEventCategory',
            'group' => fn ($query) => $query->withTrashed(),
            'participants.participant' => fn (MorphTo $morph) => $morph->morphWith([Entity::class => ['entityType']]),
            'fieldValues' => fn ($query) => $query
                ->attached()
                ->with(['field', 'valueListItem', 'file', 'relatedContact', 'relatedEntity']),
        ]));
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
