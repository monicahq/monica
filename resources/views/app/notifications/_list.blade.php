{{-- The notifications listed in the bar's panel, fetched when the panel is first opened, a page at a time. --}}
{{-- @var \App\ViewModels\NotificationBarViewModel $viewModel --}}
@php($notifications = $viewModel->notifications())

<div id="notification-list" x-merge="replace" class="max-h-96 overflow-y-auto dim-when-busy">
  <x-list-panel
    :items="$notifications"
    id="notification-rows"
    merge="append"
    target="notification-rows notification-footer"
    footer-id="notification-footer"
    :next-page-url="$viewModel->nextPageUrl()"
    :shows-end="$viewModel->page() > 1"
    class="rounded-none border-0"
  >
    <x-slot:empty>
      <p class="px-3 py-6 text-center text-sm text-muted">{{ __('Nothing to catch up on.') }}</p>
    </x-slot:empty>

    <ul class="divide-y divide-hairline">
      @foreach ($notifications as $notification)
        <li>
          <form action="{{ $notification['url'] }}" method="post">
            @csrf
            @method('PUT')

            <button
              type="submit"
              @class([
                'flex w-full cursor-pointer flex-col items-start gap-0.5 px-3 py-2.5 text-left transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset',
                'bg-card' => ! $notification['is_read'],
              ])
            >
              <span class="flex w-full items-center gap-1.5">
                @unless ($notification['is_read'])
                  <span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-danger"></span>
                @endunless

                <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $notification['title'] }}</span>

                @unless ($notification['is_read'])
                  <span class="sr-only">{{ __('Not read yet.') }}</span>
                @endunless
              </span>

              <span class="text-xs text-body">{{ $notification['body'] }}</span>

              <span class="text-2xs text-muted-soft">{{ $notification['when'] }}</span>
            </button>
          </form>
        </li>
      @endforeach
    </ul>
  </x-list-panel>
</div>
