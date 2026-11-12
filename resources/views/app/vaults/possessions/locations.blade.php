{{-- The Locations section of a possession: where it is kept now and everywhere it has been. --}}
{{-- @var \App\ViewModels\Vaults\PossessionLocationsViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $current = $viewModel->current();
  $rows = $viewModel->rows();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
@endphp

<x-shell :title="__('Locations of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'locations'])

    <section
      x-data="{ moving: @js($formOpen) }"
      aria-labelledby="locations-title"
      class="flex min-w-0 flex-col gap-5"
    >
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="locations-title" class="text-section-title text-ink">{{ __('Locations') }}</h2>
            <x-help
              :label="__('Help about locations')"
              :title="__('How locations work')"
              guide="customise.possession-locations"
            >
              {{ __('Each stay is one location and the days the possession spent there. Moving it closes the current stay on the day you choose and opens the next one. The locations themselves are set up once, in Settings.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Where the possession is kept now, and everywhere it has been since it was recorded.') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <x-link :href="$viewModel->locationsUrl()" class="text-sm">{{ __('Manage locations') }}</x-link>
          <x-button size="small" type="button" x-show="! moving" @click="moving = true; $nextTick(() => document.getElementById('location-stay-location')?.focus())">
            <x-slot:icon><x-icon name="arrow-right" :size="16" /></x-slot:icon>
            {{ __('Move elsewhere') }}
          </x-button>
        </div>
      </div>

      @if ($current !== null)
        <x-panel flush>
          <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-hairline bg-card text-[#26686e] dark:text-[#5fb3bb]" aria-hidden="true">
              <x-icon name="place" :size="16" />
            </span>
            <div class="flex min-w-55 flex-1 flex-col gap-1">
              <span class="text-xs text-muted">{{ __('Kept now in') }}</span>
              <p class="flex flex-wrap items-baseline gap-1.5">
                @if ($current['head'] !== '')
                  <span class="text-sm text-muted">{{ $current['head'] }}</span>
                @endif
                <span class="text-base font-semibold text-ink">{{ $current['leaf'] }}</span>
              </p>
              @if ($current['address'] !== null)
                <span class="text-sm text-body">{{ $current['address'] }}</span>
              @endif
            </div>
            <x-button.secondary size="small" :href="$current['editUrl']">
              {{ __('Correct') }}<span class="sr-only">, {{ __('the current stay') }}</span>
            </x-button.secondary>
          </div>
          <dl class="grid grid-cols-[repeat(auto-fit,minmax(11.875rem,1fr))] border-t border-hairline">
            <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
              <dt class="text-xs text-muted">{{ __('Since') }}</dt>
              <dd class="text-sm font-semibold text-ink">{{ $current['since'] }}</dd>
            </div>
            <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
              <dt class="text-xs text-muted">{{ __('Time there') }}</dt>
              <dd class="text-sm font-semibold text-ink">{{ $current['held'] }}</dd>
            </div>
            <div class="flex flex-col gap-0.75 px-4 py-3.5">
              <dt class="text-xs text-muted">{{ __('Why it is there') }}</dt>
              <dd @class(['text-sm', 'text-ink' => $current['reason'] !== null, 'text-muted' => $current['reason'] === null])>{{ $current['reason'] ?? __('No reason recorded') }}</dd>
            </div>
          </dl>
        </x-panel>
      @else
        <x-empty-state :title="__('Not kept anywhere right now')">
          <x-slot:icon><x-icon name="place" :size="20" /></x-slot:icon>
          {{ __('No stay is open, so the possession has no current location. Move it somewhere to start a new stay.') }}
        </x-empty-state>
      @endif

      <div x-show="moving" x-cloak>
        @include('app.vaults.possessions._location-stay-form', ['viewModel' => $viewModel])
      </div>

      @if ($rows !== [])
        <div class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h3 class="text-card-title text-ink">{{ __('Every stay') }}</h3>
            <span class="text-sm text-muted">{{ $viewModel->countLine() }}</span>
            <span class="ms-auto text-xs text-muted">{{ __('Newest first') }}</span>
          </div>

          <ol class="flex flex-col gap-3">
            @foreach ($rows as $row)
              @if ($row['kind'] === 'gap')
                <li class="flex items-center gap-2 px-4 text-sm text-muted">
                  <span class="flex" aria-hidden="true"><x-icon name="info" :size="14" /></span>
                  <span>{{ $row['text'] }}</span>
                </li>
              @else
                <li
                  x-data="{ confirming: false }"
                  aria-labelledby="stay-{{ $row['id'] }}-title"
                  class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
                >
                  <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-hairline bg-card text-[#26686e] dark:text-[#5fb3bb]" aria-hidden="true">
                      <x-icon name="place" :size="15" />
                    </span>

                    <div class="flex min-w-55 flex-1 flex-col gap-1">
                      <div class="flex flex-wrap items-baseline gap-1.5">
                        @if ($row['head'] !== '')
                          <span class="text-sm text-muted">{{ $row['head'] }}</span>
                        @endif
                        <h4 id="stay-{{ $row['id'] }}-title" class="text-base font-semibold text-ink">{{ $row['leaf'] }}</h4>
                        @if ($row['isCurrent'])
                          <x-badge tone="success">{{ __('Here now') }}</x-badge>
                        @endif
                      </div>
                      <span class="text-sm text-body">{{ $row['period'] }} <span class="text-muted">· {{ $row['length'] }}</span></span>
                      <span @class(['text-sm', 'text-body' => $row['reason'] !== null, 'text-muted' => $row['reason'] === null])>{{ $row['reason'] ?? __('No reason recorded') }}</span>
                      @if ($row['note'] !== null)
                        <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-muted">{{ $row['note'] }}</p>
                      @endif
                    </div>

                    <div class="flex gap-1.5">
                      <x-button.secondary size="small" :href="$row['editUrl']">
                        {{ __('Correct') }}<span class="sr-only">, {{ $row['leaf'] }}</span>
                      </x-button.secondary>
                      <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="stay-{{ $row['id'] }}-delete">
                        {{ __('Delete') }}<span class="sr-only">, {{ $row['leaf'] }}</span>
                      </x-button>
                    </div>
                  </div>

                  <div
                    id="stay-{{ $row['id'] }}-delete"
                    x-show="confirming"
                    x-cloak
                    @keydown.escape="confirming = false"
                    role="alertdialog"
                    aria-labelledby="stay-{{ $row['id'] }}-delete-title"
                    aria-describedby="stay-{{ $row['id'] }}-delete-body"
                    class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-t border-error-edge bg-error-tint px-4 py-3"
                  >
                    <div class="min-w-60 flex-1">
                      <p id="stay-{{ $row['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $row['deleteTitle'] }}</p>
                      <p id="stay-{{ $row['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ $row['deleteWarning'] }}</p>
                    </div>
                    <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                    <x-form method="delete" :action="$row['destroyUrl']">
                      <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                    </x-form>
                  </div>
                </li>
              @endif
            @endforeach
          </ol>
        </div>
      @endif
    </section>
  </div>
</x-shell>
