{{-- Everything recorded about one life event, opened under its entry on the vault timeline. --}}
{{-- @var \App\ViewModels\Vaults\LifeEventDetailsViewModel $viewModel --}}
<div id="{{ $viewModel->panelId() }}">
  <x-panel :flush="true" aria-label="{{ __('Details of :name', ['name' => $viewModel->title()]) }}">
    <div class="flex flex-wrap items-center gap-2.5 border-b border-hairline px-4 py-3">
      <x-badge :icon="$viewModel->icon()">{{ $viewModel->typeName() }}</x-badge>

      @if ($viewModel->categoryName() !== null)
        <span class="text-xs text-muted">{{ $viewModel->categoryName() }}</span>
      @endif
    </div>

    <div class="flex flex-col gap-6 p-4">
      @if ($viewModel->narrative() !== null)
        <p class="max-w-160 text-sm/relaxed text-pretty whitespace-pre-line text-body">{{ $viewModel->narrative() }}</p>
      @endif

      <x-detail-list class="-mx-4 border-y border-hairline">
        @foreach ($viewModel->facts() as $fact)
          <x-detail-list.row :label="$fact['label']" value-class="text-sm text-pretty text-ink">
            @if ($fact['url'] !== null)
              <x-link :href="$fact['url']" data-turbo="true">{{ $fact['value'] }}</x-link>
            @else
              {{ $fact['value'] }}
            @endif
          </x-detail-list.row>
        @endforeach
      </x-detail-list>

      @if ($viewModel->people() !== [])
        <div class="flex flex-col gap-2.5">
          <x-caption tone="muted" id="{{ $viewModel->panelId() }}-people">{{ __('Who was there') }}</x-caption>

          <ul role="list" aria-labelledby="{{ $viewModel->panelId() }}-people" class="flex list-none flex-col rounded-md border border-hairline">
            @foreach ($viewModel->people() as $person)
              <li class="flex items-center gap-3 border-b border-hairline px-3 py-2.5 last:border-b-0">
                <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="28" alt="" />

                <div class="flex min-w-0 flex-1 flex-col">
                  @if ($person['url'] !== null)
                    <a href="{{ $person['url'] }}" data-turbo="true" class="self-start rounded-xs text-sm font-semibold focus-ring text-link underline decoration-hairline-strong underline-offset-3 hover:decoration-ink">{{ $person['name'] }}</a>
                  @else
                    <span class="text-sm font-semibold text-ink">{{ $person['name'] }}</span>
                  @endif

                  @if ($person['relation'] !== null)
                    <span class="text-xs text-muted">{{ $person['relation'] }}</span>
                  @endif

                  @if ($person['notes'] !== null)
                    <span class="text-xs text-pretty text-body">{{ $person['notes'] }}</span>
                  @endif
                </div>

                @if ($person['role'] !== null)
                  <span class="shrink-0 text-xs text-body">{{ $person['role'] }}</span>
                @endif
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      @if ($viewModel->files() !== [])
        <div class="flex flex-col gap-2.5">
          <x-caption tone="muted" id="{{ $viewModel->panelId() }}-files">{{ __('Files') }}</x-caption>

          <ul role="list" aria-labelledby="{{ $viewModel->panelId() }}-files" class="flex list-none flex-wrap gap-2">
            @foreach ($viewModel->files() as $file)
              <li>
                <a href="{{ $file['url'] }}" class="flex items-center gap-2.5 rounded-md border border-hairline px-3 py-2 no-underline focus-ring hover:bg-hover">
                  <x-icon name="file" :size="16" class="text-muted" />
                  <span class="flex flex-col">
                    <span class="text-sm font-medium text-ink">{{ $file['name'] }}</span>
                    <span class="text-xs text-muted">{{ $file['size'] }}</span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    <x-slot:footer class="flex flex-wrap items-center gap-3 text-muted">
      <span>{{ $viewModel->added() }}@if ($viewModel->changed() !== null) {{ $viewModel->changed() }}@endif</span>

      <x-button.secondary
        type="button"
        size="small"
        class="ms-auto"
        x-on:click="open = false; $nextTick(() => $refs.detailsToggle.focus())"
      >{{ __('Close') }}</x-button.secondary>
    </x-slot:footer>
  </x-panel>
</div>
