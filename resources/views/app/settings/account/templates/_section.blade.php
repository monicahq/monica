{{--
  One section on the template, with the fields inside it.

  The heading shown here is this template's, not the section's. A section is
  shared, so renaming it from inside one template would reach into every other
  template carrying it; what is edited here is the override, which is display
  only and changes no identity.

  When the section is one this template cannot write to, because it has been
  published or because another template carries it too, the row says so. The
  first edit will give the section a new version, and having read that
  beforehand is the difference between a feature and a surprise.

  @var array $section
  @var \App\Models\Template $template
  @var bool $isEditable
  @var array<int, array{id: int, uuid: string, label: string}> $otherPages
--}}
<div data-row-id="{{ $section['id'] }}" class="relative">
  <div
    x-cloak
    x-show="hoveringId === {{ $section['id'] }} && draggingId !== {{ $section['id'] }}"
    class="absolute inset-x-0 -top-2 z-10 h-0.5 bg-brand"
    aria-hidden="true"
  ></div>

  <div
    @if ($isEditable)
      draggable="true"
      @dragstart="start($event, {{ $section['id'] }})"
      @dragover.prevent="over($event, {{ $section['id'] }})"
      @drop.prevent="drop($event, {{ $section['id'] }})"
      @dragend="stop()"
      :class="draggingId === {{ $section['id'] }} && 'opacity-40'"
    @endif
    x-data="{ renaming: false }"
    class="rounded-xl border border-hairline bg-canvas"
  >
    <div class="flex flex-wrap items-center gap-2.5 border-b border-hairline px-3.5 py-3">
      <div x-show="! renaming" class="flex min-w-0 flex-1 flex-wrap items-center gap-2.5">
        <h2 class="truncate text-lg font-semibold text-ink">{{ $section['name'] }}</h2>

        @if ($section['isMine'])
          <span class="rounded-sm border border-hairline bg-sunken px-2 py-0.5 text-xs text-muted">{{ __('Your section') }}</span>
        @endif

        <span class="ml-auto font-mono text-xs text-muted-soft">{{ $section['fieldLabel'] }}</span>

        @if ($isEditable)
          <div class="flex shrink-0 items-center gap-0.5">
            <button
              type="button"
              @click="renaming = true; $nextTick(() => $refs.title.focus())"
              class="cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
            >{{ __('Rename') }}</button>

            {{--
              Offered only once there is somewhere to move it to. The select
              submits on its own, since picking a page is the whole decision and
              a button beside it would only ask for the same thing twice.
            --}}
            @if ($otherPages !== [])
              <x-form
                method="put"
                :action="route('settings.templateSectionPage.update', [$template, $section['id']])"
                x-target="builder"
                class="flex items-center"
              >
                <select
                  name="page"
                  aria-label="{{ __('Move this section to another page') }}"
                  @change="$el.form.requestSubmit()"
                  class="cursor-pointer appearance-none rounded-md border-none bg-transparent px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink"
                >
                  <option value="" selected>{{ __('Move to') }}</option>

                  @foreach ($otherPages as $page)
                    <option value="{{ $page['uuid'] }}">{{ $page['label'] }}</option>
                  @endforeach
                </select>
              </x-form>
            @endif

            <x-modal.warning
              :title="__('Take “:name” off this template?', ['name' => $section['name']])"
              :confirm="__('Take it off')"
              :action="route('settings.templateSections.destroy', [$template, $section['id']])"
              x-target="builder"
            >
              <x-slot:trigger>
                <button
                  type="button"
                  @click="open = true"
                  class="cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-error"
                >{{ __('Remove') }}</button>
              </x-slot:trigger>

              <p>{{ __('The section itself is untouched, and every other template carrying it is unchanged.') }}</p>
            </x-modal.warning>
          </div>
        @endif
      </div>

      @if ($isEditable)
        <div x-cloak x-show="renaming" class="min-w-0 flex-1">
          <x-form
            method="put"
            :action="route('settings.templateSections.update', [$template, $section['id']])"
            x-target="builder"
            class="flex flex-wrap items-end gap-3"
          >
            <x-input
              :id="'section-'.$section['id'].'-title'"
              name="title_override"
              x-ref="title"
              :value="$section['name']"
              :aria-label="__('Heading on this template')"
              :help="__('Only this template sees it.')"
              class="max-w-72"
              maxlength="255"
            />

            <div class="flex items-center gap-2">
              <x-button>{{ __('Save') }}</x-button>

              <button type="button" @click="renaming = false" class="cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink">{{ __('Cancel') }}</button>
            </div>
          </x-form>
        </div>
      @endif
    </div>

    @if ($section['willBeCopied'] && $isEditable)
      <p class="border-b border-hairline-soft bg-sunken px-3.5 py-2 text-xs leading-relaxed text-muted">
        {{ __('This section is published or shared with another template. Changing anything in it here gives the section a new version, and leaves the other templates exactly as they are.') }}
      </p>
    @endif

    <div class="space-y-0.5 p-2">
      @foreach ($section['fields'] as $field)
        <a
          href="{{ $field['url'] }}"
          x-target="builder"
          @class([
            'grid w-full grid-cols-[minmax(90px,1fr)_minmax(0,auto)] items-center gap-2.5 rounded-md border px-2.5 py-2.5 text-left transition-colors duration-150 hover:bg-hover',
            'border-hairline-strong bg-sunken' => $field['isSelected'],
            'border-transparent' => ! $field['isSelected'],
          ])
        >
          <span class="grid min-w-0 gap-px">
            <span class="truncate text-sm font-semibold text-ink">{{ $field['label'] }}</span>

            <span class="truncate text-xs leading-snug text-muted">{{ $field['help'] ?? '—' }}</span>
          </span>

          <span class="flex max-w-48 flex-wrap items-center justify-end gap-1.5">
            @if ($field['isSensitive'])
              <span class="rounded-full border border-hairline bg-sunken px-2.5 py-0.5 text-xs text-warning">{{ __('Sensitive') }}</span>
            @endif

            <span class="rounded-full border border-hairline bg-sunken px-2.5 py-0.5 text-xs whitespace-nowrap text-body">{{ $field['typeLabel'] }}</span>
          </span>
        </a>
      @endforeach

      @if ($isEditable)
        <a
          href="{{ route('settings.fields.new', [$template, $section['id']]) }}"
          data-turbo="true"
          class="flex w-full items-center gap-2.5 rounded-md border border-transparent px-2.5 py-2.5 text-sm text-body transition-colors duration-150 hover:bg-hover hover:text-ink"
        >
          <span class="flex w-4 justify-center text-muted" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
              <line x1="8" y1="3.5" x2="8" y2="12.5"></line>
              <line x1="3.5" y1="8" x2="12.5" y2="8"></line>
            </svg>
          </span>
          {{ __('Add a field') }}
        </a>
      @endif
    </div>
  </div>

  @if ($isEditable)
    <x-form
      method="put"
      :action="route('settings.templateSectionPosition.update', [$template, $section['id']])"
      x-target="builder"
      :data-position-form="$section['id']"
      class="hidden"
    >
      <input type="hidden" name="position" value="" />
    </x-form>
  @endif
</div>
