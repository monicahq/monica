<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateEntity;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTemplateChanged;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\EntitiesViewModel;
use App\ViewModels\Vaults\EntityFormViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntityController extends Controller
{
    public function index(Request $request, Vault $vault, EntityType $entityType): View
    {
        $this->refuseAHiddenType($vault, $entityType);

        return view('app.vaults.entities.index', [
            'viewModel' => new EntitiesViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
            ),
        ]);
    }

    public function new(Request $request, Vault $vault, EntityType $entityType): View
    {
        $this->refuseAHiddenType($vault, $entityType);

        abort_unless($request->user()->can('addEntity', [$entityType, $vault]), 404);

        return view('app.vaults.entities.form', [
            'viewModel' => new EntityFormViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                version: $entityType->defaultVersionIn($vault) ?? abort(404),
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, EntityType $entityType): RedirectResponse
    {
        $this->refuseAHiddenType($vault, $entityType);

        $fields = $entityType->defaultVersionIn($vault)?->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn()) ?? collect();

        $validated = $request->validate(
            array_merge([
                'name' => ['required', 'string', 'max:255'],
                'structure_version_id' => ['required', 'integer'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
            ], FieldValueRules::for($fields, $vault)),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        try {
            $entity = new CreateEntity(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                name: $validated['name'],
                structureVersion: StructureVersion::query()->find($request->integer('structure_version_id')),
                values: ($validated['fields'] ?? []) + array_fill_keys($fields->pluck('id')->all(), null),
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
            )->execute();
        } catch (AuthorizationException) {
            return redirect()->route('entities.index', ['vault' => $vault, 'entityType' => $entityType]);
        } catch (EntityNeedsAName|EntityTemplateChanged $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('entities.index', ['vault' => $vault, 'entityType' => $entityType])
            ->with('status', __(':name is written down.', ['name' => $entity->name]));
    }

    private function refuseAHiddenType(Vault $vault, EntityType $entityType): void
    {
        abort_unless(
            EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(),
            404,
        );
    }
}
