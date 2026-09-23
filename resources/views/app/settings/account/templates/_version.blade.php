{{-- One version on the timeline, and everything it holds once it is opened. --}}
{{--
  @var \App\ViewModels\Settings\Account\Templates\StructureVersionEntry $entry
  @var bool $hasConnector
--}}
<li
  x-data="{
    open: false,
    seeLabel: @js(__('See what is in :version', ['version' => $entry->label()])),
    hideLabel: @js(__('Hide what is in :version', ['version' => $entry->label()])),
  }"
  class="flex items-stretch gap-5"
>
  <div class="flex w-5 shrink-0 flex-col items-center pt-1.5" aria-hidden="true">
    <span @class([
      'size-2.75 shrink-0 rounded-full border-[1.5px] border-ink',
      'bg-canvas' => $entry->isDraft(),
      'bg-ink' => ! $entry->isDraft(),
    ])></span>

    @if ($hasConnector)
      <span class="-mb-1.5 w-px flex-1 bg-hairline"></span>
    @endif
  </div>

  <div class="min-w-0 flex-1 pb-9">
    <button
      type="button"
      @click="open = ! open"
      :aria-expanded="open ? 'true' : 'false'"
      class="block w-full cursor-pointer rounded-md text-left focus-ring"
    >
      <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
        <code class="font-mono text-base font-semibold text-ink">{{ $entry->label() }}</code>

        <x-badge :tone="$entry->tone()">{{ $entry->statusLabel() }}</x-badge>

        <span class="text-sm font-semibold text-ink">{{ $entry->dateLine() }}</span>

        <code class="ml-auto font-mono text-xs text-muted">{{ $entry->basedOn() }}</code>
      </span>

      <span class="mt-2 block max-w-prose text-sm text-pretty text-body">{{ $entry->changeNote() }}</span>

      <span class="mt-1.5 block text-sm text-muted">{{ $entry->summary() }} · {{ $entry->counts() }}</span>

      <span class="mt-2.5 inline-flex items-center gap-1.5 text-sm font-medium text-ink">
        <span x-text="open ? hideLabel : seeLabel">{{ __('See what is in :version', ['version' => $entry->label()]) }}</span>

        <x-icon
          name="chevron-down"
          :size="15"
          :stroke-width="1.6"
          class="text-muted transition-transform duration-(--duration-standard)"
          ::class="open && 'rotate-180'"
        />
      </span>
    </button>

    <div x-cloak x-show="open" x-transition class="mt-4 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
      @foreach ($entry->pages() as $page)
        <div>
          <div class="flex items-center gap-2.5 border-b border-hairline bg-card px-4 py-2.5">
            <span class="text-2xs tracking-caption text-muted uppercase">{{ $page['position'] }}</span>

            <span class="text-sm font-semibold text-ink">{{ $page['label'] }}</span>

            <code class="ml-auto font-mono text-xs text-muted">{{ $page['meta'] }}</code>
          </div>

          @forelse ($page['sections'] as $section)
            <div
              x-data="{
                shown: false,
                moreLabel: @js(__('View more')),
                lessLabel: @js(__('View less')),
              }"
              class="border-b border-hairline last:border-b-0"
            >
              <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
                <x-section-mark :icon="$section['icon']" :colour="$section['colour']" :size="26" />

                <span class="text-sm font-semibold text-ink">{{ $section['name'] }}</span>

                <code class="font-mono text-xs text-muted">{{ $section['version'] }}</code>

                @if ($section['isNew'])
                  <x-badge tone="info">{{ __('New in this version') }}</x-badge>
                @endif

                <span class="ml-auto text-sm text-muted">{{ $section['fieldLabel'] }}</span>

                <button
                  type="button"
                  @click="shown = ! shown"
                  :aria-expanded="shown ? 'true' : 'false'"
                  class="shrink-0 cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
                  x-text="shown ? lessLabel : moreLabel"
                >{{ __('View more') }}</button>
              </div>

              <div x-cloak x-show="shown" x-transition class="mx-4 mb-3.5 ml-15 rounded-md border border-hairline bg-card">
                @forelse ($section['fields'] as $field)
                  @include('app.settings.account.templates._version-field', ['field' => $field, 'nested' => false])
                @empty
                  <p class="px-3.5 py-2.5 text-sm text-muted">{{ __('This section carries no field.') }}</p>
                @endforelse
              </div>
            </div>
          @empty
            <p class="px-4 py-3 text-sm text-muted">{{ __('This page carries no section.') }}</p>
          @endforelse
        </div>
      @endforeach
    </div>
  </div>
</li>
