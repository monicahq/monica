{{-- The pairs of contacts in a vault that look like the same person, strongest resemblance first. --}}
{{-- @var \App\ViewModels\Vaults\ContactDuplicatesViewModel $viewModel --}}
@php
  $pairs = $viewModel->pairs();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <div class="flex flex-wrap items-end gap-4">
    <div class="min-w-60 flex-1">
      <x-page-header :title="$viewModel->title()" />
    </div>

    <x-form method="post" :action="$viewModel->searchUrl()" class="shrink-0">
      <x-button size="small">
        <x-icon name="search" :size="15" :stroke-width="1.8" />
        {{ __('Look for duplicates') }}
      </x-button>
    </x-form>
  </div>

  @if ($pairs->isEmpty())
    <x-panel>
      <x-empty-state :title="__('Nothing looks doubled')" :description="$viewModel->emptyLine()">
        <x-slot:icon>
          <x-icon name="contact" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        <x-slot:action>
          <x-button.secondary :href="$viewModel->peopleUrl()" data-turbo="true" size="small">
            {{ __('Back to the people') }}
          </x-button.secondary>
        </x-slot:action>
      </x-empty-state>
    </x-panel>
  @else
    <x-panel :title="__('Possible duplicates')" icon="contact" :flush="true">
      <ul class="grid gap-0">
        @foreach ($pairs as $candidate)
          @php
            $row = $viewModel->row($candidate);
          @endphp

          <li class="grid gap-2 border-b border-hairline px-4.5 py-3.5 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
            <div class="grid gap-0.5">
              <span class="text-sm font-semibold text-ink">
                {{ __(':name and :other', ['name' => $row['name'], 'other' => $row['other_name']]) }}
              </span>

              <span class="text-sm text-ink-soft">{{ $row['why'] }}</span>
            </div>

            <div class="flex items-center gap-2">
              <x-button.secondary :href="$row['compare_url']" data-turbo="true" size="small">
                {{ __('Compare them') }}
              </x-button.secondary>

              <x-form method="put" :action="$row['dismiss_url']">
                <x-button.ghost type="submit" size="small">
                  {{ __('Two different people') }}
                </x-button.ghost>
              </x-form>
            </div>
          </li>
        @endforeach
      </ul>
    </x-panel>

    <x-pagination :paginator="$pairs" :size="$viewModel->size()" :default="$viewModel->defaultSize()" />
  @endif
</x-shell>
