{{-- One group: a box to write what it did or what to remember, its shared history, reminders and files. --}}
{{-- @var \App\ViewModels\Vaults\GroupOverviewViewModel $viewModel --}}
@php
  $header = $viewModel->header();
  $vault = $header->vault();
  $history = $viewModel->history();
  $reminders = $viewModel->reminders();
  $files = $viewModel->files();
@endphp
<x-shell :title="$header->name()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups'), 'url' => $header->indexUrl()], ['label' => $header->name()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$header->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  @include('app.vaults.groups._header', ['viewModel' => $header])

  <div class="flex flex-col gap-6">
    @include('app.vaults.groups._tabs', ['viewModel' => $header, 'current' => 'overview'])

    <div class="grid items-start gap-x-6 gap-y-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
      <div class="flex min-w-0 flex-col gap-8">
        @include('app.vaults.groups._composer', ['viewModel' => $viewModel])

        @include('app.vaults.groups._history', ['history' => $history])
      </div>

      <div class="flex min-w-0 flex-col gap-5">
        <x-panel :title="__('Reminders')" icon="reminder" :flush="true" :clip="false">
          <x-slot:action>
            <x-button.ghost :href="$reminders->newUrl()" data-turbo="true" size="small">
              <x-slot:icon><x-icon name="plus" :size="15" /></x-slot:icon>
              {{ __('Add') }}
            </x-button.ghost>
          </x-slot:action>

          @if ($reminders->holdsNothing())
            <p class="p-4 text-sm text-pretty text-muted">{{ __('Nothing to be reminded of. Something like organising the next game, or sending the holiday card.') }}</p>
          @else
            <ul class="list-none">
              @foreach ($reminders->rows() as $reminder)
                @php
                  $row = $reminder['row'];
                @endphp
                <li class="flex flex-col gap-2.5 border-b border-hairline px-4 py-3 last:border-b-0">
                  <div class="flex items-start gap-3">
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                      @if ($row->reminderUrl() !== null)
                        <a href="{{ $row->reminderUrl() }}" data-turbo="true" class="rounded-xs font-semibold text-pretty text-ink no-underline hover:underline focus-ring">{{ $row->text() }}</a>
                      @else
                        <span class="font-semibold text-pretty text-ink">{{ $row->text() }}</span>
                      @endif

                      <span class="flex items-center gap-1.5 text-xs text-muted">
                        @if ($reminder['repeats'])
                          <x-icon name="calendar-repeat" :size="13" :stroke-width="1.5" />
                        @endif
                        {{ collect([$row->whenLine(), $row->cadence()])->filter()->implode(' · ') }}
                      </span>
                    </div>

                    @include('app.vaults.reminders._menu', ['row' => $row])
                  </div>

                  @if ($row->canRemember() || $row->canForget())
                    <div class="flex flex-wrap gap-2">
                      @include('app.vaults.reminders._answers', ['row' => $row])
                    </div>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif
        </x-panel>

        @if ($files !== null)
          @php
            $latest = $files->latest();
          @endphp
          <x-panel :title="__('Files')" icon="file" :flush="true">
            <x-slot:action>
              <x-button.ghost :href="$files->indexUrl()" data-turbo="true" size="small">
                <x-slot:icon><x-icon name="plus" :size="15" /></x-slot:icon>
                {{ __('Add') }}
              </x-button.ghost>
            </x-slot:action>

            @if ($latest === [])
              <p class="p-4 text-sm text-pretty text-muted">{{ __('A group photo, a schedule or a registration form, kept with the group.') }}</p>
            @else
              <ul class="list-none">
                @foreach ($latest as $file)
                  <li class="flex min-w-0 items-center gap-2.5 border-b border-hairline px-4 py-2.5 last:border-b-0">
                    <span class="flex size-7.5 flex-none items-center justify-center rounded-md border border-hairline bg-card text-ink-soft" aria-hidden="true">
                      <x-icon :name="$file['icon']" :size="15" />
                    </span>

                    <span class="min-w-0 flex-1">
                      <a href="{{ $file['url'] }}" data-no-instant class="block font-medium break-all text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">{{ $file['name'] }}</a>
                      <span class="block truncate text-xs text-muted">{{ $file['meta'] }}</span>
                    </span>
                  </li>
                @endforeach
              </ul>
            @endif
          </x-panel>
        @elseif ($viewModel->keptFilesLine() !== null)
          <p class="text-sm text-pretty text-muted">{{ $viewModel->keptFilesLine() }}</p>
        @endif
      </div>
    </div>
  </div>
</x-shell>
