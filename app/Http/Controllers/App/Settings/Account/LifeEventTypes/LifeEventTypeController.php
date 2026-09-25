<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\LifeEventTypes;

use App\Actions\ActivateLifeEventType;
use App\Actions\CreateLifeEventType;
use App\Actions\DeactivateLifeEventType;
use App\Actions\DestroyLifeEventType;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Actions\UpdateLifeEventType;
use App\Enums\FieldTypeEnum;
use App\Enums\VaultColourEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Section;
use App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypeFormViewModel;
use App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypesViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LifeEventTypeController extends Controller
{
    /**
     * The settings that are counted rather than written out, which are checked
     * as numbers wherever they turn up.
     *
     * @var list<string>
     */
    private const array COUNTED_SETTINGS = [
        'max_length',
        'min',
        'max',
        'decimals',
        'max_choices',
        'repeat_min',
        'repeat_max',
    ];

    public function index(Request $request, ?LifeEventCategory $lifeEventCategory = null): View
    {
        return view('app.settings.account.life-event-types.index', [
            'viewModel' => new LifeEventTypesViewModel(
                user: $request->user(),
                selected: $lifeEventCategory,
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.settings.account.life-event-types.form', [
            'viewModel' => new LifeEventTypeFormViewModel(
                user: $request->user(),
                wantedCategory: $this->wantedCategory($request),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        [$groups, $fields] = $this->layout($request);

        try {
            $lifeEventType = DB::transaction(function () use ($request, $validated, $groups, $fields): LifeEventType {
                $lifeEventType = new CreateLifeEventType(
                    user: $request->user(),
                    lifeEventCategory: $this->category($request, (int) $validated['life_event_category_id']),
                    name: $validated['name'],
                    description: $validated['description'] ?? null,
                    icon: $validated['icon'] ?? null,
                    colour: $validated['colour'] ?? null,
                )->execute();

                new ReplaceLifeEventTypeFields(
                    user: $request->user(),
                    lifeEventType: $lifeEventType,
                    groups: $groups,
                    fields: $fields,
                )->execute();

                if (! $request->boolean('is_active')) {
                    new DeactivateLifeEventType(user: $request->user(), lifeEventType: $lifeEventType)->execute();
                }

                return $lifeEventType;
            });
        } catch (AcceptedFileKindIsNotValid $e) {
            return redirect()->route('settings.lifeEventTypes.new')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventType->lifeEventCategory)
            ->with('status', __('The type is added.'))
            ->with('status_description', __('It is offered the next time you record a life event.'));
    }

    public function edit(Request $request, LifeEventType $lifeEventType): View
    {
        return view('app.settings.account.life-event-types.form', [
            'viewModel' => new LifeEventTypeFormViewModel(
                user: $request->user(),
                lifeEventType: $lifeEventType,
            ),
        ]);
    }

    public function update(Request $request, LifeEventType $lifeEventType): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        [$groups, $fields] = $this->layout($request);

        try {
            DB::transaction(function () use ($request, $lifeEventType, $validated, $groups, $fields): void {
                new UpdateLifeEventType(
                    user: $request->user(),
                    lifeEventType: $lifeEventType,
                    lifeEventCategory: $this->category($request, (int) $validated['life_event_category_id']),
                    name: $validated['name'],
                    description: $validated['description'] ?? null,
                    icon: $validated['icon'] ?? null,
                    colour: $validated['colour'] ?? null,
                )->execute();

                new ReplaceLifeEventTypeFields(
                    user: $request->user(),
                    lifeEventType: $lifeEventType,
                    groups: $groups,
                    fields: $fields,
                )->execute();

                $this->settleAvailability($request, $lifeEventType);
            });
        } catch (AuthorizationException|AcceptedFileKindIsNotValid $e) {
            return redirect()->route('settings.lifeEventTypes.edit', $lifeEventType)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventType->refresh()->lifeEventCategory)
            ->with('status', __('The type is saved.'))
            ->with('status_description', __('The life events already recorded with it read in the new words.'));
    }

    public function destroy(Request $request, LifeEventType $lifeEventType): RedirectResponse
    {
        $category = $lifeEventType->lifeEventCategory;

        try {
            new DestroyLifeEventType(
                user: $request->user(),
                lifeEventType: $lifeEventType,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.lifeEventTypes.edit', $lifeEventType)
                ->with('error', $e->getMessage())
                ->with('error_description', __('Stop offering it instead, and the life events recorded with it keep reading as they do.'));
        }

        return redirect()->route('settings.lifeEventTypes.category', $category)
            ->with('status', __('The type is deleted.'))
            ->with('status_description', __('No life event was recorded with it.'));
    }

    private function settleAvailability(Request $request, LifeEventType $lifeEventType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $lifeEventType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateLifeEventType(user: $request->user(), lifeEventType: $lifeEventType)->execute();

            return;
        }

        new DeactivateLifeEventType(user: $request->user(), lifeEventType: $lifeEventType)->execute();
    }

    /**
     * The groups and the fields as the action wants them: in the order somebody
     * put them in, and with a field whose group has just been taken away filed
     * under no group rather than under one that is gone.
     *
     * @return array{0: list<array{reference: string, placement_id: ?int, name: string}>, 1: list<array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}>}
     */
    private function layout(Request $request): array
    {
        $groups = [];

        foreach ((array) $request->input('groups', []) as $reference => $group) {
            $groups[] = [
                'reference' => (string) $reference,
                'placement_id' => ($group['placement_id'] ?? null) === null ? null : (int) $group['placement_id'],
                'name' => (string) $group['name'],
                'position' => (int) ($group['position'] ?? 0),
            ];
        }

        usort($groups, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        $references = array_column($groups, 'reference');
        $fields = [];

        foreach ((array) $request->input('fields', []) as $reference => $field) {
            $group = (string) ($field['group'] ?? '');

            $fields[] = [
                'key' => ($field['key'] ?? null) === null ? null : (string) $field['key'],
                'group' => in_array($group, $references, true) ? $group : '',
                'label' => (string) $field['label'],
                'type' => (string) $field['type'],
                'is_required' => (bool) ($field['is_required'] ?? false),
                'help_text' => ($field['help_text'] ?? null) === null ? null : (string) $field['help_text'],
                'settings' => array_map(strval(...), array_filter((array) ($field['settings'] ?? []), fn (mixed $value): bool => $value !== null)),
                'choices' => array_values(array_filter(
                    array_map(fn (mixed $choice): string => trim((string) $choice), (array) ($field['choices'] ?? [])),
                    fn (string $choice): bool => $choice !== '',
                )),
                'position' => (int) ($field['position'] ?? 0),
            ];
        }

        usort($fields, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return [
            array_map(fn (array $group): array => array_diff_key($group, ['position' => null]), $groups),
            array_map(fn (array $field): array => array_diff_key($field, ['position' => null]), $fields),
        ];
    }

    private function wantedCategory(Request $request): ?LifeEventCategory
    {
        $wanted = $request->integer('category');

        if ($wanted === 0) {
            return null;
        }

        return LifeEventCategory::query()
            ->where('account_id', $request->user()->account_id)
            ->find($wanted);
    }

    private function category(Request $request, int $id): LifeEventCategory
    {
        return LifeEventCategory::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($id);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'life_event_category_id' => [
                'required',
                Rule::exists('life_event_categories', 'id')->where('account_id', $request->user()->account_id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
            'is_active' => ['nullable', 'boolean'],

            'groups' => ['nullable', 'array'],
            'groups.*.placement_id' => ['nullable', 'integer'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.position' => ['nullable', 'integer', 'min:0'],

            'fields' => ['nullable', 'array'],
            'fields.*.key' => ['nullable', 'string', 'max:64'],
            'fields.*.group' => ['nullable', 'string', 'max:64'],
            'fields.*.position' => ['nullable', 'integer', 'min:0'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::enum(FieldTypeEnum::class)],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.help_text' => ['nullable', 'string', 'max:255'],
            'fields.*.settings' => ['nullable', 'array'],
            'fields.*.settings.*' => ['nullable', 'string', 'max:255'],
            'fields.*.choices' => ['nullable', 'array'],
            'fields.*.choices.*' => ['nullable', 'string', 'max:255'],
        ];

        foreach (self::COUNTED_SETTINGS as $setting) {
            $rules["fields.*.settings.{$setting}"] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }
}
