<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateEntity;
use App\Actions\UpdateEntity;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTemplateChanged;
use App\Exceptions\EntityTemplateNotOffered;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Helpers\PageSize;
use App\Helpers\RecordKind;
use App\Helpers\TemplateFilter;
use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\EntitiesViewModel;
use App\ViewModels\Vaults\EntityFormViewModel;
use App\ViewModels\Vaults\EntityViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class EntityController extends Controller
{
    public function index(Request $request, Vault $vault, EntityType $entityType): View
    {
        $this->refuseAHiddenType($vault, $entityType);

        $validated = $request->validate([
            'template' => ['nullable', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'change' => ['nullable', 'string', 'uuid'],
        ]);

        return view('app.vaults.entities.index', [
            'viewModel' => new EntitiesViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                size: PageSize::from($request, EntitiesViewModel::PER_PAGE),
                templateFilter: TemplateFilter::fromValidated(
                    kind: RecordKind::forEntityType(vault: $vault, entityType: $entityType),
                    template: $validated['template'] ?? null,
                    versionNumber: isset($validated['version']) ? (int) $validated['version'] : null,
                    change: $validated['change'] ?? null,
                ),
            ),
        ]);
    }

    public function new(Request $request, Vault $vault, EntityType $entityType, ?string $template = null): View
    {
        $this->refuseAHiddenType($vault, $entityType);

        abort_unless($request->user()->can('addEntity', [$entityType, $vault]), 404);

        $onOffer = $entityType->versionsOnOfferIn($vault);

        $version = $template === null
            ? $onOffer->first()
            : $onOffer->first(fn (StructureVersion $version): bool => $version->structure->owner->slug === $template);

        return view('app.vaults.entities.form', [
            'viewModel' => new EntityFormViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                version: $version ?? abort(404),
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, EntityType $entityType): RedirectResponse
    {
        $this->refuseAHiddenType($vault, $entityType);

        $version = StructureVersion::query()
            ->where('account_id', $request->user()->account_id)
            ->find($request->integer('structure_version_id'));

        abort_unless($version instanceof StructureVersion, 404);

        $fields = $version->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn());

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
                structureVersion: $version,
                values: ($validated['fields'] ?? []) + array_fill_keys($fields->pluck('id')->all(), null),
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
            )->execute();
        } catch (AuthorizationException) {
            return redirect()->route('entities.index', ['vault' => $vault, 'entityType' => $entityType]);
        } catch (EntityNeedsAName|EntityTemplateChanged|EntityTemplateNotOffered $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('entities.index', ['vault' => $vault, 'entityType' => $entityType])
            ->with('status', __(':name is written down.', ['name' => $entity->name]));
    }

    public function show(Request $request, Vault $vault, EntityType $entityType, Entity $entity, ?string $page = null): View
    {
        $this->refuseWhatIsNotReachable($vault, $entityType, $entity);

        $viewModel = new EntityViewModel(
            user: $request->user(),
            vault: $vault,
            entity: $entity,
            selectedPageKey: $page,
        );

        abort_if($page !== null && $viewModel->selectedPage() === null, 404);

        return view('app.vaults.entities.show', [
            'viewModel' => $viewModel,
        ]);
    }

    public function edit(Request $request, Vault $vault, EntityType $entityType, Entity $entity): View
    {
        $this->refuseWhatIsNotReachable($vault, $entityType, $entity);

        return view('app.vaults.entities.form', [
            'viewModel' => new EntityFormViewModel(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                version: $entity->structureVersion,
                entity: $entity,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, EntityType $entityType, Entity $entity): RedirectResponse
    {
        $this->refuseWhatIsNotReachable($vault, $entityType, $entity);

        $fields = $entity->structureVersion->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn());

        $heldFileFieldIds = $entity->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->whereNotNull('value_file_id')
            ->pluck('field_id')
            ->all();

        $fieldRules = FieldValueRules::for($fields, $vault);

        foreach ($heldFileFieldIds as $fieldId) {
            $fieldRules["fields.{$fieldId}"] = array_map(
                fn (mixed $rule): mixed => $rule === 'required' ? 'nullable' : $rule,
                $fieldRules["fields.{$fieldId}"] ?? [],
            );
        }

        $validated = $request->validate(
            array_merge([
                'name' => ['required', 'string', 'max:255'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
            ], $fieldRules),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        $values = ($validated['fields'] ?? []) + array_fill_keys($fields->pluck('id')->all(), null);

        try {
            $entity = new UpdateEntity(
                user: $request->user(),
                vault: $vault,
                entity: $entity,
                name: $validated['name'],
                values: $values,
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
                keptFieldIds: array_values(array_filter(
                    $heldFileFieldIds,
                    fn (int $fieldId): bool => ! ($values[$fieldId] ?? null) instanceof UploadedFile,
                )),
            )->execute();
        } catch (EntityNeedsAName $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()
            ->route('entities.show', ['vault' => $vault, 'entityType' => $entityType, 'entity' => $entity])
            ->with('status', __(':name is saved.', ['name' => $entity->name]));
    }

    private function refuseWhatIsNotReachable(Vault $vault, EntityType $entityType, Entity $entity): void
    {
        $this->refuseAHiddenType($vault, $entityType);

        abort_unless($entity->entity_type_id === $entityType->id, 404);
    }

    private function refuseAHiddenType(Vault $vault, EntityType $entityType): void
    {
        abort_unless(
            EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(),
            404,
        );
    }
}
