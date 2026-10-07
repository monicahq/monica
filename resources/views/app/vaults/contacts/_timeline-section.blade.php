{{-- The timeline of somebody, the way to add a life event to it, and the offer to undo a deletion. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
<section
  id="contact-timeline"
  x-merge="replace"
  x-data="lifeEventPanel(@js($viewModel->lifeEventForm()->projection()))"
  data-life-event-panel
  aria-labelledby="contact-timeline-title"
  class="flex flex-col gap-5"
>
  <div class="flex flex-wrap items-end justify-between gap-3 px-0.5">
    <h2 id="contact-timeline-title" class="text-section-title text-ink">{{ __('Timeline') }}</h2>

    <x-button.secondary
      type="button"
      x-ref="addButton"
      x-on:click="show()"
      x-bind:aria-expanded="open ? 'true' : 'false'"
      :aria-controls="$viewModel->lifeEventForm()->isRendered() ? 'life-event-panel' : null"
      x-bind:disabled="open"
    >
      <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
      {{ __('Add a life event') }}
    </x-button.secondary>
  </div>

  @if ($deleted = $viewModel->deletedLifeEvent())
    <div x-data="{ shown: true }" x-show="shown">
      <x-notice tone="quiet" icon="check-circle">
        {{ $deleted['line'] }}

        <x-slot:action>
          <div class="flex items-center gap-2">
            <x-form method="put" :action="$deleted['restoreUrl']" x-target="contact-timeline">
              <x-button.secondary size="small" x-autofocus>{{ __('Undo') }}</x-button.secondary>
            </x-form>

            <button type="button" x-on:click="shown = false" class="focus-ring flex size-7 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink" aria-label="{{ __('Dismiss') }}">
              <x-icon name="close" :size="14" />
            </button>
          </div>
        </x-slot:action>
      </x-notice>
    </div>
  @endif

  @if ($viewModel->lifeEventForm()->isRendered() && ! ($viewModel->lifeEventForm()->isEditing() && $viewModel->timeline()->lists((int) $viewModel->lifeEventForm()->editingId())))
    @include('app.vaults.contacts._life-event-panel', ['form' => $viewModel->lifeEventForm()])
  @endif

  @include('app.vaults.contacts._timeline', ['timeline' => $viewModel->timeline(), 'form' => $viewModel->lifeEventForm()])
</section>
