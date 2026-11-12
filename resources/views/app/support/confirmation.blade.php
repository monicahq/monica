{{-- What somebody sees once their request is sent: its number, what was sent, and what comes next. --}}
{{-- @var \App\ViewModels\Support\SupportConfirmationViewModel $viewModel --}}
@php
  $supportRequest = $viewModel->supportRequest();
@endphp

<x-support.page
  :title="__('Request sent')"
  current="new"
  :open-count="$viewModel->navigation()->openRequestCount()"
  :crumbs="[['label' => __('Support'), 'url' => route('support.requests.index')], ['label' => __('Request sent')]]"
>
  <div class="mx-auto w-full max-w-3xl space-y-8">
    <header class="space-y-3">
      <span class="flex size-10 items-center justify-center rounded-full border border-success-edge bg-success-tint text-success" aria-hidden="true">
        <x-icon name="check" :size="20" :stroke-width="1.8" />
      </span>
      <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Your request has been sent') }}</h1>
      <p class="max-w-prose text-base text-body text-pretty">{{ __('The support team has it. Keep the request number below if you write about it later. A copy is on its way to :email.', ['email' => $viewModel->email()]) }}</p>
    </header>

    <x-panel :flush="true">
      <div class="flex flex-wrap items-center gap-x-6 gap-y-3 p-5">
        <div class="min-w-50 flex-1 space-y-1.5">
          <x-caption>{{ __('Request number') }}</x-caption>
          <p class="font-mono text-lg font-semibold tracking-wide text-ink">{{ $supportRequest->reference }}</p>
        </div>
        <x-copy-button :text="$supportRequest->reference" :label="__('Copy number')" :failure="__('The number could not be copied. Select it and copy it by hand.')" variant="secondary" size="small" />
      </div>

      <x-detail-list class="border-t border-hairline">
        <x-detail-list.row :label="__('Subject')">{{ $supportRequest->subject }}</x-detail-list.row>
        <x-detail-list.row :label="__('About')">{{ $viewModel->categoryLabel() }}</x-detail-list.row>
        <x-detail-list.row :label="__('Files')">{{ $viewModel->filesLine() }}</x-detail-list.row>
        <x-detail-list.row :label="__('Sent')"><x-timestamp :at="$supportRequest->created_at">{{ $viewModel->sentAt() }}</x-timestamp></x-detail-list.row>
      </x-detail-list>
    </x-panel>

    <section class="space-y-3" aria-labelledby="next-title">
      <h2 id="next-title" class="text-lg font-semibold text-ink">{{ __('What happens next') }}</h2>
      <ol class="space-y-3.5">
        @foreach ([
          __('A person from the support team reads it, usually within one working day.'),
          __('Their reply lands in Your requests and in your inbox.'),
          __('To add something, reply from the request page. Replying to the email does not reach us.'),
        ] as $step)
          <li class="flex gap-3">
            <span class="flex size-5.5 shrink-0 items-center justify-center rounded-full border border-hairline-strong text-xs font-semibold text-ink" aria-hidden="true">{{ $loop->iteration }}</span>
            <p class="text-base text-body text-pretty">{{ $step }}</p>
          </li>
        @endforeach
      </ol>
    </section>

    <div class="flex flex-wrap gap-2">
      <x-button :href="route('support.requests.show', $supportRequest)">{{ __('View this request') }}</x-button>
      <x-button.secondary :href="route('support.requests.new')">{{ __('Back to support') }}</x-button.secondary>
    </div>
  </div>
</x-support.page>
