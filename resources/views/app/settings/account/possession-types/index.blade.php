{{-- The kinds of things the account owns, each opening the screen that sets its fields. --}}
{{-- @var \App\ViewModels\Settings\Account\PossessionTypes\PossessionTypesViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="possession-types"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()">
    <x-slot:actions>
      <x-button.secondary :href="$viewModel->importUrl()" size="small" class="max-sm:w-full">{{ __('Import JSON') }}</x-button.secondary>

      <x-form method="post" :action="$viewModel->createAction()" class="max-sm:w-full">
        <x-button type="submit" size="small" class="max-sm:w-full">{{ __('New type') }}</x-button>
      </x-form>
    </x-slot:actions>
  </x-page-header>

  <x-panel :flush="true">
    @if ($viewModel->isEmpty())
      <x-empty-state :title="__('No possession types yet')">
        <x-slot:icon>
          <x-icon name="tag" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('A type says which details a thing of its kind carries. Create one, or import one from a file.') }}
      </x-empty-state>
    @else
      <ul aria-label="{{ $viewModel->title() }}">
        @foreach ($viewModel->possessionTypes() as $possessionType)
          <li class="border-b border-hairline last:border-b-0">
            <a href="{{ $possessionType['editUrl'] }}" class="flex items-center gap-3 px-4 py-3 transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset">
              <span class="flex size-8 flex-none items-center justify-center rounded-md {{ $possessionType['tint'] }}" aria-hidden="true">
                <x-icon :name="$possessionType['icon']" :size="16" />
              </span>
              <span class="sr-only">{{ $possessionType['colourName'] }}</span>

              <span class="min-w-0 flex-1">
                <span class="block truncate font-semibold text-ink">{{ $possessionType['name'] }}</span>
                <span class="mt-px block text-sm text-muted">{{ $possessionType['line'] }}</span>
              </span>

              <x-icon name="chevron-right" :size="16" class="flex-none text-muted-soft" />
            </a>
          </li>
        @endforeach
      </ul>
    @endif
  </x-panel>
</x-settings.page>
