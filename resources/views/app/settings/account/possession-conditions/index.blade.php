{{-- The states the account's possessions can be in, added, changed, moved and deleted in place. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionConditions\PossessionConditionsViewModel $viewModel --}}
@php
  $newConditionErrors = $errors->getBag('newPossessionCondition');
  $defaultColour = \App\Enums\VaultColourEnum::Blue->value;
  $tints = collect($viewModel->colours())->mapWithKeys(fn (array $option): array => [$option['value'] => $option['tint']])->all();
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-conditions"
>
  <div id="possession-conditions" x-merge="replace" class="flex flex-col gap-8 dim-when-busy">
    <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

    <x-page-section :title="__('Add a condition')">
      <x-panel :flush="true" :clip="false">
        <x-form
          method="post"
          :action="$viewModel->createAction()"
          x-target="possession-conditions"
          x-data="{
            name: {{ Js::from($newConditionErrors->any() ? (string) old('name') : '') }},
            icon: {{ Js::from($newConditionErrors->any() ? (string) old('icon') : '') }},
            colour: {{ Js::from($newConditionErrors->any() ? (string) old('colour', $defaultColour) : $defaultColour) }},
            tints: {{ Js::from($tints) }},
          }"
        >
          <div class="flex flex-wrap items-end gap-4 p-4">
            <x-mark-picker
              id="possession-condition-mark"
              layout="split"
              :glyphs="$viewModel->glyphs()"
              :colours="$viewModel->colours()"
              :icon="$newConditionErrors->any() ? old('icon') : null"
              :colour="$newConditionErrors->any() ? old('colour', $defaultColour) : $defaultColour"
            />

            <div class="min-w-55 flex-1">
              <x-input
                id="possession-condition-name"
                name="name"
                :label="__('Name')"
                :placeholder="__('Mint')"
                :error="$newConditionErrors->get('name')"
                maxlength="50"
                required
                x-model="name"
              />
            </div>

            <x-button type="submit">
              <x-icon name="plus" :size="16" />
              {{ __('Add condition') }}
            </x-button>
          </div>

          @if ($newConditionErrors->has('icon') || $newConditionErrors->has('colour'))
            <x-error :messages="array_merge($newConditionErrors->get('icon'), $newConditionErrors->get('colour'))" class="px-4 pb-3" />
          @endif

          <div class="flex items-center gap-2.5 border-t border-hairline px-4 py-3 text-sm text-muted" aria-hidden="true">
            <span>{{ __('Preview') }}</span>

            <span class="inline-flex h-7 items-center gap-2 rounded-full py-0.5 pr-3 pl-0.5 text-sm text-body" :style="`background: ${tints[colour] ?? ''}`">
              <span class="flex size-6 items-center justify-center rounded-full bg-canvas" :style="`color: ${colour}`">
                @foreach ($viewModel->glyphs() as $glyph)
                  <span x-show="icon === @js($glyph)" style="display: none" class="flex">
                    <x-icon :name="$glyph" :size="14" :stroke-width="1.6" />
                  </span>
                @endforeach

                <span x-show="! icon" class="size-2 rounded-full" :style="`background: ${colour}`"></span>
              </span>

              <span x-text="name.trim() || @js(__('Your condition'))">{{ __('Your condition') }}</span>
            </span>
          </div>
        </x-form>
      </x-panel>
    </x-page-section>

    <section class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-0.5">
        <h2 class="text-section-title text-ink">{{ __('Your conditions') }}</h2>

        @if ($viewModel->countLine() !== '')
          <p class="text-sm text-muted">{{ $viewModel->countLine() }}</p>
        @endif
      </div>

      <x-panel :flush="true" :clip="false">
        @if ($viewModel->isEmpty())
          <x-empty-state :title="__('No conditions yet')">
            <x-slot:icon>
              <x-icon name="star" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Conditions you add here will say what state a possession is in.') }}
          </x-empty-state>
        @else
          <div x-data="sortableRows" data-sortable role="list" aria-label="{{ __('Your conditions') }}">
            @foreach ($viewModel->conditions() as $condition)
              @include('app.settings.account.possession-conditions._row', [
                'condition' => $condition,
                'glyphs' => $viewModel->glyphs(),
                'colours' => $viewModel->colours(),
              ])
            @endforeach
          </div>
        @endif
      </x-panel>
    </section>
  </div>
</x-settings.page>
