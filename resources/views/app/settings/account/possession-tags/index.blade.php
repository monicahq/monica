{{-- The words the account puts on its possessions, added, renamed and deleted in place. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionTags\PossessionTagsViewModel $viewModel --}}
@php
  $newTagErrors = $errors->getBag('newPossessionTag');
  $defaultColour = collect($viewModel->colours())->firstWhere('isDefault', true)['value'];
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-tags"
>
  <div id="possession-tags" x-merge="replace" class="flex flex-col gap-8 dim-when-busy">
    <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

    <x-page-section :title="__('Add a tag')">
      <x-panel :flush="true">
        <x-form
          method="post"
          :action="$viewModel->createAction()"
          x-target="possession-tags"
          x-data="{
            name: {{ Js::from($newTagErrors->any() ? (string) old('name') : '') }},
            colour: {{ Js::from($newTagErrors->any() ? (string) old('colour', $defaultColour) : $defaultColour) }},
          }"
        >
          <div class="flex flex-wrap items-end gap-4 p-4">
            <div class="min-w-55 flex-1">
              <x-input
                id="possession-tag-name"
                name="name"
                :label="__('Name')"
                :placeholder="__('Needs repair')"
                :error="$newTagErrors->get('name')"
                maxlength="50"
                required
                x-model="name"
              />
            </div>

            @include('app.settings.account.possession-tags._colours', [
              'colours' => $viewModel->colours(),
              'checked' => $newTagErrors->any() ? (string) old('colour', $defaultColour) : $defaultColour,
              'idPrefix' => 'possession-tag-colour',
              'model' => 'colour',
            ])

            <x-button type="submit">
              <x-icon name="plus" :size="16" />
              {{ __('Add tag') }}
            </x-button>
          </div>

          <div class="flex items-center gap-2.5 border-t border-hairline px-4 py-3 text-sm text-muted" aria-hidden="true">
            <span>{{ __('Preview') }}</span>

            <span
              class="inline-flex h-6 items-center gap-1.5 rounded-full px-2.5 text-sm text-body"
              :style="`background: color-mix(in srgb, ${colour} 14%, var(--canvas))`"
            >
              <span class="size-1.75 rounded-full" :style="`background: ${colour}`"></span>
              <span x-text="name.trim() || @js(__('Your tag'))">{{ __('Your tag') }}</span>
            </span>
          </div>
        </x-form>
      </x-panel>
    </x-page-section>

    <section class="flex flex-col gap-3">
      <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-0.5">
        <h2 class="text-section-title text-ink">{{ __('Your tags') }}</h2>

        @if ($viewModel->countLine() !== '')
          <p class="text-sm text-muted">{{ $viewModel->countLine() }}</p>
        @endif
      </div>

      <x-panel :flush="true">
        @if ($viewModel->isEmpty())
          <x-empty-state :title="__('No tags yet')">
            <x-slot:icon>
              <x-icon name="hash" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Tags you add here will be put on possessions, to sort them across types and locations.') }}
          </x-empty-state>
        @else
          <ul aria-label="{{ __('Your tags') }}">
            @foreach ($viewModel->tags() as $tag)
              @include('app.settings.account.possession-tags._row', [
                'tag' => $tag,
                'colours' => $viewModel->colours(),
              ])
            @endforeach
          </ul>
        @endif
      </x-panel>
    </section>
  </div>
</x-settings.page>
