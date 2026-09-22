{{-- The line that says where you are inside a vault, which always starts at the vault itself. --}}
{{--
  @var \App\Models\Vault $vault
  @var array<int, array{label: string, url?: string|null}> $items
  @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vault> $vaults
--}}

@php
  $tile = 'flex shrink-0 items-center justify-center rounded-xs font-semibold text-white';
  $initial = mb_strtoupper(mb_substr($vault->name, 0, 1));
  $hasChoice = $vaults->count() > 1;
  $panelId = 'vault-switcher-'.$vault->id;
@endphp

<nav {{ $attributes->class(['flex min-w-0 items-center gap-x-0.5 text-sm text-muted']) }} aria-label="{{ __('Breadcrumb') }}">
  @if ($hasChoice)
    <div x-data="{ open: false }" @ajax:before.window="open = false" class="relative flex min-w-0">
      <button
        type="button"
        x-ref="trigger"
        @click="open = ! open"
        @click.outside="open = false"
        @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
        ::aria-expanded="open ? 'true' : 'false'"
        aria-controls="{{ $panelId }}"
        @if ($items === []) aria-current="page" @endif
        class="flex min-w-0 cursor-pointer items-center gap-2 rounded-md border border-transparent px-1.5 py-1 text-left text-sm text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
      >
        <span style="background: {{ $vault->colour }}" class="{{ $tile }} size-5.5 text-[11px]" aria-hidden="true">{{ $initial }}</span>

        <span class="truncate font-semibold">{{ $vault->name }}</span>

        <span class="sr-only">{{ __('Switch to another vault') }}</span>

        <x-icon
          name="chevron-down"
          :size="14"
          :stroke-width="1.6"
          class="shrink-0 text-muted transition-transform duration-(--duration-standard)"
          ::class="open && 'rotate-180'"
        />
      </button>

      <div
        x-cloak
        x-show="open"
        x-anchor.bottom-start.offset.6="$refs.trigger"
        x-transition:enter="transition ease-out duration-[170ms]"
        x-transition:enter-start="opacity-0"
        x-transition:leave="transition ease-out duration-[110ms]"
        x-transition:leave-end="opacity-0"
        id="{{ $panelId }}"
        class="z-40 w-74 max-w-[calc(100vw-2rem)] rounded-lg border border-hairline bg-canvas p-1.5 shadow-pop"
      >
        <x-caption tone="muted" class="block px-2.5 pt-1.5 pb-1">{{ __('Your vaults') }}</x-caption>

        <ul class="flex flex-col gap-0.5">
          @foreach ($vaults as $option)
            <li class="flex">
              <a
                href="{{ route('vaults.show', $option) }}"
                data-turbo="true"
                @if ($option->is($vault)) aria-current="true" @endif
                class="flex w-full items-center gap-2.5 rounded-sm px-2.5 py-2 no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset aria-[current=true]:bg-hover"
              >
                <span style="background: {{ $option->colour }}" class="{{ $tile }} size-6 text-[11px]" aria-hidden="true">{{ mb_strtoupper(mb_substr($option->name, 0, 1)) }}</span>

                <span class="flex min-w-0 flex-1 flex-col">
                  <span class="truncate text-[13px] font-semibold text-ink">{{ $option->name }}</span>

                  <span class="text-[11px] text-muted">{{ trans_choice(':count person|:count people', $option->contact_count) }}</span>
                </span>

                <span class="flex w-4 shrink-0 items-center justify-center text-ink {{ $option->is($vault) ? '' : 'opacity-0' }}">
                  <x-icon name="check" :size="15" :stroke-width="2" />
                </span>
              </a>
            </li>
          @endforeach
        </ul>

        <div class="-mx-1.5 mt-1.5 border-t border-hairline"></div>

        <a
          href="{{ route('vaults.new') }}"
          data-turbo="true"
          class="mt-1.5 flex items-center gap-2 rounded-sm px-2.5 py-2 text-[13px] text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring-inset"
        >
          <x-icon name="plus" :size="15" :stroke-width="1.6" />

          {{ __('New vault') }}
        </a>
      </div>
    </div>
  @elseif ($items === [])
    <span class="flex min-w-0 items-center gap-2 border border-transparent px-1.5 py-1 text-ink" aria-current="page">
      <span style="background: {{ $vault->colour }}" class="{{ $tile }} size-5.5 text-[11px]" aria-hidden="true">{{ $initial }}</span>

      <span class="truncate font-semibold">{{ $vault->name }}</span>
    </span>
  @else
    <a
      href="{{ route('vaults.show', $vault) }}"
      data-turbo="true"
      class="flex min-w-0 items-center gap-2 rounded-md border border-transparent px-1.5 py-1 text-ink no-underline transition-colors duration-(--duration-fast) ease-out focus-ring"
    >
      <span style="background: {{ $vault->colour }}" class="{{ $tile }} size-5.5 text-[11px]" aria-hidden="true">{{ $initial }}</span>

      <span class="truncate font-semibold">{{ $vault->name }}</span>
    </a>
  @endif

  @foreach ($items as $item)
    <x-icon name="chevron-right" :size="14" :stroke-width="1.6" class="shrink-0 text-placeholder" />

    @if (! $loop->last && ($item['url'] ?? null) !== null)
      <a
        href="{{ $item['url'] }}"
        data-turbo="true"
        class="max-w-40 truncate rounded-md px-1.5 py-1 transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring sm:max-w-60"
      >{{ $item['label'] }}</a>
    @else
      <span
        @class(['max-w-40 truncate px-1.5 py-1 sm:max-w-60', 'font-semibold text-ink' => $loop->last])
        @if ($loop->last) aria-current="page" @endif
      >{{ $item['label'] }}</span>
    @endif
  @endforeach
</nav>
