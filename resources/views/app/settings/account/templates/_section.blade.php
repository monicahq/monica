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
  @var bool $isShareable
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
    class="overflow-hidden rounded-lg border border-hairline bg-canvas "
  >
    <header class="flex min-h-13 items-center gap-2.5 border-b border-hairline px-4.5 py-3">
      <div x-show="! renaming" class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1.5">
        <h2 class="truncate text-card-title text-ink">{{ $section['name'] }}</h2>

        @if ($section['isMine'])
          <x-badge>{{ __('Your section') }}</x-badge>
        @endif

        <code class="ml-auto font-mono text-[12.5px] text-muted">{{ $section['fieldLabel'] }}</code>

        {{--
          A section leaves on its own, which is the reason a section is its own
          object rather than a grouping inside a template. The button is here on
          the section rather than only on the template, since somebody who wants
          to hand over one block should not have to hand over the rest of what
          they built around it.
        --}}
        @if ($isShareable)
          <x-button.ghost
            :href="route('settings.sectionShare.index', [$template, $section['id']])"
            data-turbo="true"
            size="small"
            class="shrink-0"
          >
            <x-slot:icon>
              <x-icon name="relationship" :size="15" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Share this section') }}
          </x-button.ghost>
        @endif

        @if ($isEditable)
          <div class="flex shrink-0 items-center gap-0.5">
            <x-button.ghost
              size="small"
              type="button"
              @click="renaming = true; $nextTick(() => $refs.title.focus())"
            >{{ __('Rename') }}</x-button.ghost>

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
                  class="inline-flex h-8.5 cursor-pointer appearance-none items-center rounded-md px-2 font-sans text-[13.5px] font-semibold text-ink-soft transition-[background-color,color] duration-(--duration-fast) ease-out hover:bg-sand hover:text-ink"
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
                <x-button.ghost size="small" type="button" @click="open = true" class="hover:text-error">{{ __('Remove') }}</x-button.ghost>
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
              size="small"
              :value="$section['name']"
              :aria-label="__('Heading on this template')"
              :help="__('Only this template sees it.')"
              class="max-w-72"
              maxlength="255"
            />

            <div class="flex items-center gap-2">
              <x-button size="small">{{ __('Save') }}</x-button>

              <x-button.ghost size="small" type="button" @click="renaming = false">{{ __('Cancel') }}</x-button.ghost>
            </div>
          </x-form>
        </div>
      @endif
    </header>

    @if ($section['willBeCopied'] && $isEditable)
      <p class="px-4 py-2.5 text-xs text-pretty text-muted border-b border-hairline bg-sunken">
        {{ __('This section is published or shared with another template. Changing anything in it here gives the section a new version, and leaves the other templates exactly as they are.') }}
      </p>
    @endif

    <div class="flex flex-col gap-0.5 p-2">
      @foreach ($section['fields'] as $field)
        @include('app.settings.account.templates._field', ['field' => $field])
      @endforeach

      @if ($isEditable)
        <a
          href="{{ route('settings.fields.new', [$template, $section['id']]) }}"
          data-turbo="true"
          class="flex w-full items-center gap-2.5 rounded-md px-2.5 py-2.5 text-sm text-body transition-colors duration-[110ms] hover:bg-hover hover:text-ink"
        >
          <span class="flex w-4 justify-center text-muted" aria-hidden="true">
            <x-icon name="plus" :size="15" :stroke-width="1.6" />
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
