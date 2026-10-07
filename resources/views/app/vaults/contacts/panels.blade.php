{{-- The panels of one person an Alpine Ajax request targets, without the page around them. --}}
{{--
  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
  @var list<string> $targets
--}}
@if (in_array('contact-favourite', $targets, true))
  @include('app.vaults.contacts._favourite', ['viewModel' => $viewModel])
@endif

@if (in_array('contact-facts', $targets, true))
  @include('app.vaults.contacts._facts', ['viewModel' => $viewModel])
@endif

@if (in_array('contact-timeline', $targets, true))
  @include('app.vaults.contacts._timeline-section', ['viewModel' => $viewModel])
@elseif (array_intersect(['contact-timeline-rows', 'contact-timeline-pagination'], $targets) !== [])
  @include('app.vaults.contacts._timeline', ['timeline' => $viewModel->timeline(), 'form' => $viewModel->lifeEventForm()])
@endif

@if (in_array('contact-reminder-panel', $targets, true))
  @include('app.vaults.contacts._reminders', ['deadlines' => $viewModel->deadlines()])
@endif

@foreach ($viewModel->sections() as $section)
  @if (in_array($section['regionId'], $targets, true))
    @include('app.vaults.contacts._section', ['section' => $section, 'viewModel' => $viewModel])
  @endif
@endforeach

<x-toaster />
