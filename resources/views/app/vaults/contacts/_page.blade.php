{{--
  One page of the record: the sections the template puts on it, each drawn as a
  block that reads what it holds until somebody asks to edit it.

  The page used to be one form with one save at the foot of it. It is not any
  more, and the reason is that looking somebody up is what this screen is for
  most of the time: a page that is always a form is a page that always looks like
  work. What replaced it is in `_section`, and the unit of writing moved with it
  from the page to the block.

  It is its own file because it is the half of the screen the tabs swap.

  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
@php
  $sections = $viewModel->sections();
@endphp
@if ($sections === [])
  <x-panel>
    <x-empty-state :title="__('Nothing on this page yet')" icon-class="bg-sand text-muted">
      <x-slot:icon>
        <x-icon name="file" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ $viewModel->emptyPageLine() }}

      <x-slot:action>
        <x-button.secondary :href="$viewModel->templateUrl()" data-turbo="true" size="small">
          {{ __('Open the template') }}
        </x-button.secondary>
      </x-slot:action>
    </x-empty-state>
  </x-panel>
@else
  <p class="max-w-[66ch] px-0.5 text-sm text-pretty text-muted">
    {{ __('Every block reads until you say otherwise. Editing one leaves the rest of the page alone, and saving writes only that block.') }}
  </p>

  <div class="grid items-start gap-5 lg:grid-cols-2">
    @foreach ($sections as $section)
      @include('app.vaults.contacts._section', ['section' => $section])
    @endforeach
  </div>
@endif
