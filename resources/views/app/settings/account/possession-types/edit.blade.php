{{-- One possession type: its name, colour and icon, and the groups and fields its things carry. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeViewModel $viewModel --}}
@php
  $kinds = $viewModel->kinds();
@endphp
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-types"
  :back-href="$viewModel->backUrl()"
  :back-label="__('Back to possession types')"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->summary()">
    <x-slot:actions>
      <x-button.secondary :href="$viewModel->exportUrl()" size="small" class="max-sm:w-full">{{ __('Export as JSON') }}</x-button.secondary>
    </x-slot:actions>
  </x-page-header>

  <p id="possession-type-saved" class="sr-only" role="status" x-merge="update">{{ session('saved') }}</p>

  <x-page-section :title="__('Name, colour and icon')">
    <div id="possession-type-identity" class="flex flex-col gap-5">
      <x-form method="put" :action="$viewModel->updateAction()" x-target="possession-type-identity possession-type-saved" class="max-w-md">
        <input type="hidden" name="colour" value="{{ $viewModel->colour() }}" />
        <input type="hidden" name="icon" value="{{ $viewModel->icon() }}" />

        <x-input
          id="name"
          :value="$viewModel->name()"
          :label="__('Name')"
          :error="$errors->get('name')"
          maxlength="255"
          required
          @change="$el.form.requestSubmit()"
        />
      </x-form>

      <x-form method="put" :action="$viewModel->updateAction()" x-target="possession-type-identity possession-type-saved">
        <input type="hidden" name="name" value="{{ $viewModel->name() }}" />
        <input type="hidden" name="icon" value="{{ $viewModel->icon() }}" />

        <div class="flex flex-col gap-2">
          <span class="text-label text-ink" id="possession-type-colour-label">{{ __('Colour') }}</span>

          <div class="flex flex-wrap gap-2.5" role="group" aria-labelledby="possession-type-colour-label">
            @foreach ($viewModel->colours() as $option)
              <button
                type="submit"
                name="colour"
                value="{{ $option['value'] }}"
                aria-label="{{ $option['label'] }}"
                aria-pressed="{{ $option['isCurrent'] ? 'true' : 'false' }}"
                style="background: {{ $option['value'] }}"
                @class([
                  'size-8.5 cursor-pointer rounded-[10px] border-2 transition-transform duration-(--duration-fast) ease-out hover:-translate-y-px focus-ring',
                  'border-ink inset-ring-2 inset-ring-canvas' => $option['isCurrent'],
                  'border-transparent shadow-control' => ! $option['isCurrent'],
                ])
              ></button>
            @endforeach
          </div>
        </div>
      </x-form>

      <x-form method="put" :action="$viewModel->updateAction()" x-target="possession-type-identity possession-type-saved">
        <input type="hidden" name="name" value="{{ $viewModel->name() }}" />
        <input type="hidden" name="colour" value="{{ $viewModel->colour() }}" />

        <div class="flex flex-col gap-2">
          <span class="text-label text-ink" id="possession-type-icon-label">{{ __('Icon') }}</span>

          <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="possession-type-icon-label">
            <button
              type="submit"
              name="icon"
              value=""
              aria-pressed="{{ $viewModel->icon() === '' ? 'true' : 'false' }}"
              @class([
                'flex size-8.5 cursor-pointer items-center justify-center rounded-md border text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring',
                'border-ink bg-hover' => $viewModel->icon() === '',
                'border-hairline bg-canvas' => $viewModel->icon() !== '',
              ])
            >
              <x-icon name="close" :size="14" />
              <span class="sr-only">{{ __('No icon') }}</span>
            </button>

            @foreach ($viewModel->glyphs() as $glyph)
              <button
                type="submit"
                name="icon"
                value="{{ $glyph['value'] }}"
                aria-pressed="{{ $glyph['isCurrent'] ? 'true' : 'false' }}"
                @class([
                  'flex size-8.5 cursor-pointer items-center justify-center rounded-md border transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring',
                  'border-ink bg-hover text-ink' => $glyph['isCurrent'],
                  'border-hairline bg-canvas text-body' => ! $glyph['isCurrent'],
                ])
              >
                <x-icon :name="$glyph['value']" :size="16" />
                <span class="sr-only">{{ $glyph['value'] }}</span>
              </button>
            @endforeach
          </div>
        </div>
      </x-form>
    </div>
  </x-page-section>

  <div id="possession-type-fields" class="flex flex-col gap-8">
    @if ($viewModel->holdsNothing())
      <x-page-section :title="__('Fields')">
        <x-panel flush>
          <div class="grid md:grid-cols-2">
            <div class="flex flex-col gap-4 border-b border-hairline bg-card p-6 md:border-e md:border-b-0 md:p-8" aria-hidden="true">
              <div class="flex flex-col gap-3 rounded-lg border border-dashed border-hairline-strong p-5">
                <p class="flex items-baseline gap-3">
                  <x-caption tone="muted">{{ __('Group') }}</x-caption>
                  <span class="font-semibold text-ink">{{ __('Purchase') }}</span>
                </p>

                <p class="flex items-center gap-3 text-muted">
                  <x-icon name="calendar" :size="18" :stroke-width="1.5" />
                  {{ __('Purchase date') }}
                </p>

                <p class="flex items-center gap-3 text-muted">
                  <x-icon name="coin" :size="18" :stroke-width="1.5" />
                  {{ __('Price') }}
                </p>
              </div>

              <x-caption tone="muted">{{ __('Standalone field') }}</x-caption>

              <p class="flex items-center gap-3 rounded-lg border border-dashed border-hairline-strong p-5 text-muted">
                <x-icon name="tag" :size="18" :stroke-width="1.5" />
                {{ __('Registration') }}
              </p>
            </div>

            <div class="flex flex-col justify-center gap-4 p-6 md:p-8">
              <p class="text-section-title text-ink">{{ __('No fields yet') }}</p>

              <p class="text-base text-pretty text-muted">{{ __('Start with the one thing you always want to know about each item, such as a reference number or a date. Add a group when a few fields belong together, like Purchase or Technical details.') }}</p>

              <div class="mt-1 flex flex-wrap gap-2.5">
                <x-form method="post" :action="$viewModel->createFieldAction()" x-target="possession-type-fields possession-type-saved">
                  <x-button type="submit" :x-autofocus="session('focus') === 'add-field'">{{ __('Add a field') }}</x-button>
                </x-form>

                <x-form method="post" :action="$viewModel->createGroupAction()" x-target="possession-type-fields possession-type-saved">
                  <x-button.secondary type="submit" :x-autofocus="session('focus') === 'add-group'">{{ __('Add a group') }}</x-button.secondary>
                </x-form>
              </div>
            </div>
          </div>
        </x-panel>
      </x-page-section>
    @else
      <x-page-section :title="__('Field groups')" :description="__('Gather related fields under a name, such as Purchase or Technical details.')">
        <x-slot:actions>
          <x-form method="post" :action="$viewModel->createGroupAction()" x-target="possession-type-fields possession-type-saved">
            <x-button.secondary type="submit" size="small" :x-autofocus="session('focus') === 'add-group'">{{ __('Add a group') }}</x-button.secondary>
          </x-form>
        </x-slot:actions>

        <div class="flex flex-col gap-4">
          @forelse ($viewModel->groups() as $group)
            @include('app.settings.account.possession-types._group', ['group' => $group, 'kinds' => $kinds])
          @empty
            <p class="text-sm text-muted">{{ __('No groups yet.') }}</p>
          @endforelse
        </div>
      </x-page-section>

      <x-page-section :title="__('Standalone fields')" :description="__('Fields that belong to the type itself, outside any group.')">
        <x-slot:actions>
          <x-form method="post" :action="$viewModel->createFieldAction()" x-target="possession-type-fields possession-type-saved">
            <x-button.secondary type="submit" size="small" :x-autofocus="session('focus') === 'add-field'">{{ __('Add a field') }}</x-button.secondary>
          </x-form>
        </x-slot:actions>

        <div class="flex flex-col gap-3">
          @forelse ($viewModel->standaloneFields() as $field)
            @include('app.settings.account.possession-types._field', ['field' => $field, 'kinds' => $kinds])
          @empty
            <p class="text-sm text-muted">{{ __('Every field of this type sits in a group.') }}</p>
          @endforelse
        </div>
      </x-page-section>
    @endif
  </div>

  <x-delete-section :title="__('Delete this type')">
    <p>{{ __('Its groups and fields are deleted with it, and so is every possession of this type, with the details it held.') }}</p>

    <x-slot:trigger>
      <x-delete-action
        :title="__('Delete “:name”?', ['name' => $viewModel->name()])"
        :confirm="__('Delete the type')"
        :action="$viewModel->deleteAction()"
        :label="__('Delete this type')"
        variant="danger"
      >
        <p>{{ __('Its groups and fields are deleted with it, and so is every possession of this type, with the details it held.') }}</p>
      </x-delete-action>
    </x-slot:trigger>
  </x-delete-section>
</x-settings.page>
