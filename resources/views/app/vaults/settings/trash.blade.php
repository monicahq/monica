{{-- What was deleted from a vault, waiting to be restored or deleted for good. --}}
{{-- @var \App\ViewModels\Vaults\VaultTrashViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $current = $viewModel->currentEntry();
@endphp
<x-shell :title="__('Trash')">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Vault settings'), 'url' => route('vaults.settings.index', ['vault' => $vault])], ['label' => __('Trash')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="vault-settings" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  @include('app.vaults.settings._tabs', ['vault' => $vault, 'current' => 'trash', 'trashCount' => $viewModel->trashCount()])

  <section aria-label="{{ __('Trash') }}" class="flex flex-col gap-4">
    @if ($viewModel->holdsNothing())
      <x-panel>
        <x-empty-state :title="__('The trash is empty')">
          <x-slot:icon>
            <x-icon name="check-circle" :size="24" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}
        </x-empty-state>
      </x-panel>
    @else
      <div class="flex flex-wrap items-center gap-2">
        <span id="trash-show-label" class="text-sm text-muted">{{ __('Show') }}</span>

        <x-actions-menu :label="__('Show')" :text="$current['label']" labelledby="trash-show-label trash-show-trigger" trigger-id="trash-show-trigger" align="left" width="wide">
          <x-slot:glyph>
            <x-icon :name="$current['icon']" :size="15" :stroke-width="1.5" />
          </x-slot:glyph>

          @foreach ($viewModel->menu() as $entry)
            <x-actions-menu.item :href="$entry['url']" :checked="$entry['checked']">
              <x-slot:icon>
                <x-icon :name="$entry['icon']" :size="15" :stroke-width="1.5" class="text-muted" />
              </x-slot:icon>

              <span class="min-w-0 flex-1">{{ $entry['label'] }}</span>
              <span class="text-xs text-muted tabular-nums">{{ $entry['count'] }}</span>
            </x-actions-menu.item>
          @endforeach
        </x-actions-menu>

        <x-help
          :label="__('Help about the trash')"
          :title="__('How long does something stay in the trash?')"
          guide="vaults.trash"
        >{{ __('Whatever you delete from this vault waits here for 30 days, then it is deleted for good. Restoring something puts it back where it was, along with anything deleted with it.') }}</x-help>

        <div class="ml-auto">
          <x-delete-action
            :title="__('Empty the trash?')"
            :confirm="__('Empty the trash')"
            :cancel="__('Keep them')"
            :action="$viewModel->emptyUrl()"
            :label="__('Empty the trash')"
            size="small"
          >
            @foreach ($viewModel->emptyLines() as $line)
              <p>{{ $line }}</p>
            @endforeach
          </x-delete-action>
        </div>
      </div>

      <x-panel flush>
        <div class="overflow-x-auto">
          <table class="w-full min-w-120 border-collapse text-left">
            <thead>
              <tr class="border-b border-hairline text-xs font-medium text-muted">
                <th scope="col" class="h-11 px-4 font-medium">{{ __('What was deleted') }}</th>
                <th scope="col" class="h-11 w-40 px-3 font-medium">{{ __('Deleted') }}</th>
                <th scope="col" class="h-11 w-56 px-4"><span class="sr-only">{{ __('Actions') }}</span></th>
              </tr>
            </thead>

            <tbody>
              @forelse ($viewModel->rows() as $row)
                <tr class="border-b border-hairline-soft last:border-b-0 hover:bg-hover">
                  <td class="px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                      @if ($row['isPerson'])
                        <x-avatar-initials :name="$row['title']" :size="32" />
                      @else
                        <span class="flex size-8 flex-none items-center justify-center rounded-md border border-hairline bg-card text-ink-soft" aria-hidden="true">
                          <x-icon :name="$row['icon']" :size="16" :stroke-width="1.5" />
                        </span>
                      @endif

                      <div class="flex min-w-0 flex-col gap-0.5">
                        <p class="truncate text-sm font-semibold text-ink">{{ $row['title'] }}</p>
                        <p class="truncate text-xs text-muted">{{ $row['typeLabel'] }}@if ($row['context']) · {{ $row['context'] }}@endif</p>

                        @if ($row['obstacle'])
                          <p id="{{ $row['obstacleId'] }}" class="mt-0.5 flex items-center gap-1.5 text-xs text-body">
                            <x-icon name="info" :size="13" class="shrink-0" />
                            {{ $row['obstacle'] }}
                          </p>
                        @endif
                      </div>
                    </div>
                  </td>

                  <td class="px-3 py-3">
                    <div class="flex flex-col items-start gap-1">
                      <span class="text-sm text-body">{{ $row['deletedAgo'] }}</span>

                      <x-tooltip :text="$row['tooltip']" focusable>
                        <span @class([
                          'flex items-center gap-1 text-xs tabular-nums',
                          'font-semibold text-warning' => $row['isUrgent'],
                          'text-muted' => ! $row['isUrgent'],
                        ])>
                          <x-icon :name="$row['isUrgent'] ? 'warning' : 'clock'" :size="13" :stroke-width="1.6" />
                          {{ $row['daysLeftLabel'] }}
                        </span>
                      </x-tooltip>
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                      @if ($row['obstacle'])
                        <x-button.secondary type="button" size="small" :aria-label="__('Restore :name', ['name' => $row['title']])" aria-disabled="true" aria-describedby="{{ $row['obstacleId'] }}">
                          {{ __('Restore') }}
                        </x-button.secondary>
                      @else
                        <x-form method="put" :action="$row['restoreUrl']">
                          <x-button.secondary size="small" :aria-label="__('Restore :name', ['name' => $row['title']])">{{ __('Restore') }}</x-button.secondary>
                        </x-form>
                      @endif

                      <x-delete-action
                        :title="$row['destroyTitle']"
                        :confirm="__('Delete for good')"
                        :cancel="$row['keepLabel']"
                        :action="$row['destroyUrl']"
                        :label="__('Delete for good')"
                        :trigger-label="__('Delete for good: :name', ['name' => $row['title']])"
                        variant="ghost"
                        size="small"
                      >
                        @foreach ($row['destroyLines'] as $line)
                          <p>{{ $line }}</p>
                        @endforeach
                      </x-delete-action>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="px-4 py-10 text-center text-sm text-muted">{{ $viewModel->nothingOfThisKindLine() }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </x-panel>

      <x-pagination :paginator="$viewModel->items()" :size="$viewModel->size()" :default="$viewModel->defaultSize()" />
    @endif
  </section>
</x-shell>
