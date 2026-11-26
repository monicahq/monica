{{-- One support request as its author reads it: the thread, and the way to answer or close it. --}}
{{-- @var \App\ViewModels\Support\SupportRequestViewModel $viewModel --}}
@php
  $supportRequest = $viewModel->supportRequest();
@endphp

<x-support.page
  :title="$supportRequest->subject"
  current="requests"
  :open-count="$viewModel->navigation()->openRequestCount()"
  :crumbs="[['label' => __('Your requests'), 'url' => route('support.requests.index')], ['label' => $supportRequest->reference]]"
>
  <div class="mx-auto w-full max-w-3xl space-y-8">
    <header class="space-y-2.5">
      <h1 class="text-2xl font-semibold tracking-tight text-ink text-pretty">{{ $supportRequest->subject }}</h1>
      <div class="flex flex-wrap items-center gap-x-3.5 gap-y-2">
        <x-badge :tone="$viewModel->statusTone()" :icon="$viewModel->statusIcon()">{{ $viewModel->statusLabel() }}</x-badge>
        <p class="text-sm text-body"><span class="font-mono text-ink">{{ $supportRequest->reference }}</span> · {{ $viewModel->categoryLabel() }} · {{ __('Opened :date', ['date' => $viewModel->openedOn()]) }}</p>
      </div>
    </header>

    <section aria-labelledby="conversation-title">
      <h2 id="conversation-title" class="sr-only">{{ __('Conversation') }}</h2>
      <x-panel :flush="true">
        @foreach ($viewModel->messages() as $message)
          @include('app.support._message', ['message' => $message])
        @endforeach
      </x-panel>
    </section>

    <section class="space-y-3" aria-labelledby="reply-title">
      <h2 id="reply-title" class="text-lg font-semibold text-ink">{{ __('Reply') }}</h2>

      @if ($viewModel->isSolved())
        <x-notice id="solved-notice" tone="success" :title="__('This request is solved')">
          {{ __('If it comes back, write below. Sending a reply reopens it with the same number.') }}
        </x-notice>
      @endif

      <x-panel>
        <x-form method="post" :action="route('support.requests.messages.create', $supportRequest)" :upload="true" class="space-y-4">
          <x-textarea id="body" name="body" :label="__('Your reply')" :rows="4" :value="old('body')" :error="$errors->get('body')" described-by="body-markdown" required />

          <x-support.markdown-hint id="body-markdown" />

          <x-support.file-input
            id="files"
            :accept="$viewModel->acceptedTypes()"
            :maximum-files="$viewModel->maximumFiles()"
            :maximum-kilobytes="$viewModel->maximumKilobytes()"
            :error="array_merge($errors->get('files'), $errors->get('files.*'))"
          />

          <x-button>{{ __('Send reply') }}</x-button>
        </x-form>

        <x-slot:footer>
          <x-form method="post" :action="route('support.requests.solution.create', $supportRequest)" class="flex justify-end">
            @if ($viewModel->solving()->allowed())
              <x-button.ghost>{{ __('Mark as solved') }}</x-button.ghost>
            @else
              <x-button.ghost type="button" aria-disabled="true" aria-describedby="solved-notice">{{ __('Mark as solved') }}</x-button.ghost>
            @endif
          </x-form>
        </x-slot:footer>
      </x-panel>
    </section>
  </div>
</x-support.page>
