<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\CreatePossessionField;
use App\Actions\DestroyPossessionField;
use App\Actions\UpdatePossessionField;
use App\Enums\PossessionFieldKindEnum;
use App\Http\Controllers\Controller;
use App\Models\PossessionField;
use App\Models\PossessionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PossessionFieldController extends Controller
{
    public function create(Request $request, PossessionType $possessionType): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['bail', 'nullable', 'uuid', Rule::exists('possession_field_groups', 'uuid')->where('possession_type_id', $possessionType->id)],
        ]);

        new CreatePossessionField(
            user: $request->user(),
            possessionType: $possessionType,
            group: isset($validated['group'])
                ? $possessionType->groups()->where('uuid', $validated['group'])->firstOrFail()
                : null,
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('The field is added.'));
    }

    public function update(Request $request, PossessionType $possessionType, PossessionField $possessionField): RedirectResponse
    {
        abort_unless($possessionField->possession_type_id === $possessionType->id, 404);

        $validated = $request->validateWithBag("field{$possessionField->uuid}", [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(PossessionFieldKindEnum::class)],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdatePossessionField(
                user: $request->user(),
                field: $possessionField,
                name: $validated['name'],
                kind: PossessionFieldKindEnum::from($validated['kind']),
                options: array_values($validated['options'] ?? []),
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag("field{$possessionField->uuid}");
        }

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('Saved.'));
    }

    public function destroy(Request $request, PossessionType $possessionType, PossessionField $possessionField): RedirectResponse
    {
        abort_unless($possessionField->possession_type_id === $possessionType->id, 404);

        new DestroyPossessionField(
            user: $request->user(),
            field: $possessionField,
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('The field is removed.'))
            ->with('focus', 'add-field');
    }
}
