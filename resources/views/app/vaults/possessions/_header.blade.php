{{-- The top of a possession's page: its type, where it is kept, its name, its tags, and Edit. --}}
{{-- @var \App\ViewModels\Vaults\PossessionViewModel $page --}}
@php
  $header = $page->header();
  $item = 'flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base transition-colors hover:bg-hover focus-ring-inset';
@endphp

<header class="flex flex-wrap items-start gap-5">
  <div class="flex min-w-70 flex-1 flex-col gap-2.5">
    <p class="flex flex-wrap items-center gap-2 text-sm">
      <span class="inline-flex items-center gap-1.5 rounded-full py-0.5 ps-1 pe-2.5 font-medium {{ $header['tint'] }}">
        <span class="flex size-5 items-center justify-center" aria-hidden="true">
          <x-icon :name="$header['icon']" :size="14" />
        </span>
        {{ $header['type'] }}
      </span>

      @if ($header['place'] === null)
        <span class="text-muted">{{ __('No location') }}</span>
      @elseif ($header['place']['holder'] === null)
        <span class="text-muted">{{ __('kept in') }}</span>
        <span class="text-body">{{ $header['place']['name'] }}</span>
      @else
        <span class="text-muted">{{ __('kept in') }}</span>
        <span class="text-body">{{ $header['place']['name'] }}</span>
        <span class="text-muted">{{ __('at :place', ['place' => $header['place']['holder']]) }}</span>
      @endif
    </p>

    <div class="flex items-center gap-2.5">
      <h1 class="text-page-title text-ink">{{ $page->name() }}</h1>

      @if ($header['isFeatured'])
        <span class="flex text-[#9a6a24] dark:text-[#d9a441]">
          <x-icon name="star-filled" :size="18" :label="__('Featured')" />
        </span>
      @endif
    </div>

    @include('app.vaults.possessions._header-tags', ['page' => $page])
  </div>

  <x-button.split :label="__('Edit')" :href="$page->editUrl()">
    <x-slot:menu>
      <x-form method="put" :action="$page->featuredUrl()">
        <input type="hidden" name="is_featured" value="{{ $page->isFeatured() ? '0' : '1' }}" />

        <button type="submit" role="menuitem" class="{{ $item }} text-ink">
          <span class="shrink-0" aria-hidden="true"><x-icon name="star" :size="15" /></span>
          {{ $page->isFeatured() ? __('Remove from featured') : __('Add to featured') }}
        </button>
      </x-form>

      <a href="{{ $page->recordValuationUrl() }}" role="menuitem" class="{{ $item }} text-ink no-underline">
        <span class="shrink-0" aria-hidden="true"><x-icon name="coin" :size="15" /></span>
        {{ __('Record a valuation') }}
      </a>

      <div class="my-1.5 h-px bg-hairline" role="separator"></div>

      <x-modal.warning
        :title="__('Delete :name?', ['name' => $page->name()])"
        :confirm="__('Delete the possession')"
        :action="$page->destroyUrl()"
      >
        <x-slot:trigger>
          <button type="button" role="menuitem" @click="open = true" class="{{ $item }} text-error">
            <span class="shrink-0" aria-hidden="true"><x-icon name="close" :size="15" /></span>
            {{ __('Delete this possession') }}
          </button>
        </x-slot:trigger>

        <p>{{ __('Its field values, its transactions, its insurance records and its valuations go with it. This cannot be undone.') }}</p>
      </x-modal.warning>
    </x-slot:menu>
  </x-button.split>
</header>
