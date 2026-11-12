{{-- The tags under a possession's name, and the panel that puts them on, creates them and takes them off. --}}
{{-- @var \App\ViewModels\Vaults\PossessionViewModel $page --}}
@php
  $tags = $page->header()['tags'];
  $nextColour = $page->nextTagColour();
  $targets = 'possession-tag-chips possession-tag-options possession-tag-count possession-tag-add-label';
@endphp

<div
  x-data="possessionTagPicker({
    createUrl: @js($page->createTagUrl()),
    labels: @js(['create' => __('Create “:name” and add it')]),
  })"
  class="mt-0.5 flex flex-wrap items-center gap-1.5"
>
  <x-form method="put" x-ref="form" x-target="{{ $targets }}" @ajax:after="closeUnlessRefused()" @ajax:error="window.location.reload()" class="hidden">
    <input type="hidden" name="name" x-ref="name" />
  </x-form>

  <ul id="possession-tag-chips" class="contents" aria-label="{{ __('Tags on this possession') }}">
    @foreach ($tags as $tag)
      <li class="inline-flex h-7 items-center gap-1.5 rounded-full ps-2.5 pe-0.5 text-sm font-medium {{ $tag['tint'] }}">
        <span class="size-1.75 rounded-full" style="background: {{ $tag['colour'] }}" aria-hidden="true"></span>
        {{ $tag['name'] }}

        <x-modal.warning
          :title="__('Remove “:name” from this possession?', ['name' => $tag['name']])"
          :confirm="__('Remove')"
          :action="$tag['removeUrl']"
          x-target="{{ $targets }}"
        >
          <x-slot:trigger>
            <button
              type="button"
              id="remove-tag-{{ $tag['id'] }}"
              @click="open = true"
              aria-label="{{ __('Remove “:name” from this possession', ['name' => $tag['name']]) }}"
              title="{{ __('Remove “:name” from this possession', ['name' => $tag['name']]) }}"
              class="focus-ring flex size-5.5 cursor-pointer items-center justify-center rounded-full text-muted hover:bg-ink/8 hover:text-ink"
            >
              <x-icon name="close" :size="12" />
            </button>
          </x-slot:trigger>

          <p>{{ __('The tag stays in your account for your other possessions.') }}</p>
        </x-modal.warning>
      </li>
    @endforeach
  </ul>

  <div class="relative" @click.outside="close(false)" @keydown.escape.stop.prevent="close()">
    <button
      type="button"
      x-ref="add"
      aria-haspopup="listbox"
      aria-expanded="false"
      :aria-expanded="open ? 'true' : 'false'"
      @click="toggle()"
      class="focus-ring flex h-7 cursor-pointer items-center gap-1.5 rounded-full border border-dashed border-hairline-strong px-3 text-xs font-medium text-body hover:bg-hover aria-expanded:bg-hover"
    >
      <x-icon name="plus" :size="13" />
      <span id="possession-tag-add-label">{{ $tags === [] ? __('Add a tag to sort this possession') : __('Add a tag') }}</span>
    </button>

    <div
      x-show="open"
      x-cloak
      role="dialog"
      aria-label="{{ __('Tags for this possession') }}"
      :style="`inset-inline-start: ${offset}`"
      class="absolute top-9 z-30 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
    >
      <div class="border-b border-hairline p-2">
        <label for="possession-tag-query" class="block px-1 pt-1 pb-1.5 text-xs text-muted">{{ __('Find or create a tag') }}</label>

        <div class="relative">
          <span class="pointer-events-none absolute start-2.5 top-2.5 flex text-muted" aria-hidden="true"><x-icon name="search" :size="15" /></span>

          <input
            id="possession-tag-query"
            type="text"
            x-ref="search"
            x-model="query"
            @input="search()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="chooseActive()"
            @keydown.tab="close(false)"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="true"
            aria-controls="possession-tag-listbox"
            @if ($errors->has('name')) aria-invalid="true" aria-describedby="possession-tag-error" @endif
            :aria-activedescendant="active === '' ? null : active"
            autocomplete="off"
            placeholder="{{ __('Find or create a tag') }}"
            class="focus-ring-field h-9 w-full rounded-md border border-hairline-strong bg-input ps-8 pe-2.5 text-[16px] text-ink sm:text-sm"
          />
        </div>
      </div>

      <div id="possession-tag-options">
        <x-error id="possession-tag-error" :messages="$errors->get('name')" class="px-3 pt-2" />

        <ul id="possession-tag-listbox" role="listbox" aria-multiselectable="true" aria-label="{{ __('Your tags') }}" class="flex max-h-66 flex-col gap-px overflow-y-auto p-1.5">
          @foreach ($page->tagOptions() as $option)
            <li
              id="possession-tag-option-{{ $option['id'] }}"
              role="option"
              aria-selected="{{ $option['carried'] ? 'true' : 'false' }}"
              data-tag-option
              data-id="{{ $option['id'] }}"
              data-name="{{ $option['name'] }}"
              data-url="{{ $option['url'] }}"
              data-carried="{{ $option['carried'] ? 'true' : 'false' }}"
              x-show="matches($el.dataset.name)"
              @click="choose($el)"
              @mousemove="active = $el.id"
              :class="active === $el.id && 'bg-hover'"
              class="flex min-h-9 cursor-pointer items-center gap-2.5 rounded-md px-2"
            >
              <span
                class="flex size-4 flex-none items-center justify-center rounded-sm border {{ $option['carried'] ? 'border-primary bg-primary text-on-primary' : 'border-hairline-strong bg-canvas' }}"
                aria-hidden="true"
              >
                @if ($option['carried'])
                  <x-icon name="check" :size="12" :stroke-width="2" />
                @endif
              </span>
              <span class="size-2 flex-none rounded-full" style="background: {{ $option['colour'] }}" aria-hidden="true"></span>
              <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $option['name'] }}</span>
              <span class="text-xs text-muted tabular-nums">{{ $option['others'] }}</span>
            </li>
          @endforeach

          <li
            id="possession-tag-create"
            role="option"
            aria-selected="false"
            x-show="canCreate"
            x-cloak
            @click="create()"
            @mousemove="active = $el.id"
            :class="active === $el.id && 'bg-hover'"
            class="flex cursor-pointer items-start gap-2.5 rounded-md p-2"
          >
            <span class="mt-px flex text-ink" aria-hidden="true"><x-icon name="plus" :size="15" /></span>
            <span class="flex min-w-0 flex-1 flex-col gap-0.5">
              <span class="text-sm font-medium text-ink" x-text="createLine"></span>
              <span class="flex items-center gap-1.5 text-xs text-muted">
                <span class="size-1.75 flex-none rounded-full" style="background: {{ $nextColour['colour'] }}" aria-hidden="true"></span>
                {{ __('Gets the colour :colour. You can change it in Possession tags.', ['colour' => $nextColour['name']]) }}
              </span>
            </span>
          </li>
        </ul>
      </div>

      <div class="flex items-center gap-2 border-t border-hairline bg-card px-3 py-2">
        <span id="possession-tag-count" class="flex-1 text-xs text-muted">{{ $page->tagCount() }}</span>
        <a href="{{ $page->manageTagsUrl() }}" class="focus-ring rounded-sm text-xs text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Manage tags') }}</a>
      </div>
    </div>
  </div>
</div>
