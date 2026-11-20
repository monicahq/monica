<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ApiKeyScopeEnum;
use App\Helpers\Birthday;
use App\Models\Contact;
use App\Models\ValueListItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;
use Override;

/** @mixin Contact */
class ContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $birthday = $this->birthdayFor($request);

        return [
            'type' => 'contact',
            'id' => $this->uuid,
            'attributes' => [
                'slug' => $this->slug,
                'name' => $this->name,
                'formatted_name' => $this->formatted_name,
                'name_representations' => ContactNameRepresentationResource::collection($this->nameRepresentations)->resolve($request),
                'prefix' => $this->prefix,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'maiden_name' => $this->maiden_name,
                'nickname' => $this->nickname,
                'name_order' => $this->name_order?->value,
                'initials' => $this->initials,
                'avatar_colour' => $this->avatar_colour,
                'avatar' => $this->avatarLocation(),
                'is_favourite' => $this->is_favourite,
                'is_archived' => $this->is_archived,
                'is_partial' => $this->is_partial,
                'is_me' => $this->is_me,
                'age' => $birthday?->age(),
                'next_birthday_on' => $birthday?->next()?->format('Y-m-d'),
                'structure_version_id' => $this->structure_version_id,
                'template_name' => $this->structureVersion->structure->owner->name,
                'categories' => $this->resource->heldCategories($this->reachesSensitiveFields($request))
                    ->map(fn (ValueListItem $item): array => ['id' => $item->id, 'label' => $item->label])
                    ->values()
                    ->all(),
                'last_life_event_at' => $this->last_life_event_at?->timestamp,
                'last_interaction_at' => $this->last_interaction_at?->timestamp,
                'last_consulted_at' => $this->last_consulted_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.contacts.show', [$this->vault, $this->resource]),
                'web' => route('contacts.show', [$this->vault, $this->resource]),
            ],
        ];
    }

    private function birthdayFor(Request $request): ?Birthday
    {
        $value = $this->birthday;

        if ($value === null) {
            return null;
        }

        if ($value->field->is_sensitive && ! $this->reachesSensitiveFields($request)) {
            return null;
        }

        return new Birthday(value: $value, today: CarbonImmutable::now($request->user()->timezone)->startOfDay());
    }

    private function reachesSensitiveFields(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }

    /** @return array{id: string, url: string}|null */
    private function avatarLocation(): ?array
    {
        if ($this->resource->avatar === null) {
            return null;
        }

        return [
            'id' => $this->resource->avatar->uuid,
            'url' => route('files.show', ['vault' => $this->vault, 'file' => $this->resource->avatar]),
        ];
    }
}
