<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SetFieldValues;
use App\Enums\ApiKeyScopeEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Exceptions\ValueListItemIsStopped;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Field;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ContactSectionController extends Controller
{
    public function update(Request $request, string $vault, string $contact, string $section): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();
        $section = StructureVersionSection::query()
            ->where('structure_version_id', $contact->structure_version_id)
            ->whereKey($section)
            ->firstOrFail();

        $fields = $section->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->values();

        $validated = $request->validate(
            array_merge([
                'fields' => ['array'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
                'new_choices' => ['array'],
            ], FieldValueRules::withoutFiles($fields, $vault)),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        $values = $validated['fields'] ?? [];

        $hiddenFieldIds = $this->reachesSensitiveFields($request->user()) ? [] : $fields
            ->filter(fn (Field $field): bool => $field->is_sensitive && ! array_key_exists($field->id, $values))
            ->pluck('id')
            ->all();

        try {
            new SetFieldValues(
                user: $request->user(),
                record: $contact,
                section: $section,
                values: $values,
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
                keptFieldIds: $hiddenFieldIds,
                newChoices: $validated['new_choices'] ?? [],
            )->execute();
        } catch (AuthorizationException|FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions|ValueListItemIsStopped $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar', 'nameRepresentations']),
        )->response()->setStatusCode(200);
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
