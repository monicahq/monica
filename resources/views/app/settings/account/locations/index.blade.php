{{-- The places of the account, as a tree, with the form that adds or changes one above it. --}}
{{-- @var \App\ViewModels\Settings\Account\Locations\LocationsViewModel $viewModel --}}
<x-settings.page
  :title="$viewModel->title()"
  :user="$viewModel->user()"
  current="locations"
>
  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()">
    <x-slot:actions>
      <x-button :href="$viewModel->newUrl()" size="small" class="max-sm:w-full">{{ __('New location') }}</x-button>
    </x-slot:actions>
  </x-page-header>

  @if ($viewModel->formIsOpen())
    @include('app.settings.account.locations._form', ['viewModel' => $viewModel])
  @endif

  <x-panel :flush="true">
    @if ($viewModel->isEmpty())
      <x-empty-state :title="__('No locations yet')">
        <x-slot:icon>
          <x-icon name="place" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Start with a home or a building, then add the rooms and the furniture inside it.') }}
      </x-empty-state>
    @else
      <ul aria-label="{{ $viewModel->title() }}" class="[&>li:first-child>div]:border-t-0">
        @foreach ($viewModel->tree() as $row)
          @include('app.settings.account.locations._row', ['row' => $row])
        @endforeach
      </ul>
    @endif
  </x-panel>
</x-settings.page>
