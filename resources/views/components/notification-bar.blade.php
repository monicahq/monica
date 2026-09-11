{{-- The bar in the top of every signed in screen: what the application has told you, newest first. --}}
{{-- @var \App\ViewModels\NotificationBarViewModel|null $viewModel --}}
@php
  $unread = $viewModel?->unreadCount() ?? 0;
  $notifications = $viewModel?->notifications() ?? collect();
@endphp

<div x-data="{ open: false, confirming: false }" @keydown.escape.window="open = false; confirming = false" class="relative">
  <button
    type="button"
    @click="open = ! open; confirming = false"
    @click.outside="open = false; confirming = false"
    ::aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="dialog"
    aria-controls="notification-bar-panel"
    aria-label="{{ trans_choice('Notifications, :count unread|Notifications, :count unread', $unread) }}"
    class="relative flex h-9 cursor-pointer items-center gap-1.75 rounded-md px-2.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
  >
    <x-icon name="bell" :size="18" :stroke-width="1.5" />

    @if ($unread > 0)
      <span aria-hidden="true" class="absolute top-1 right-1.5 flex size-4 items-center justify-center rounded-full bg-danger text-[10px] leading-none font-semibold text-white">
        {{ $unread > 9 ? '9+' : $unread }}
      </span>
    @endif
  </button>

  <p aria-live="polite" class="sr-only">{{ trans_choice(':count notification you have not read|:count notifications you have not read', $unread) }}</p>

  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    id="notification-bar-panel"
    role="dialog"
    aria-modal="false"
    aria-labelledby="notification-bar-heading"
    class="absolute right-0 z-20 mt-1 w-88 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
  >
    <div class="flex items-center gap-2.5 border-b border-hairline px-3 py-2.5">
      <h2 id="notification-bar-heading" class="text-[13px] font-semibold text-ink">{{ __('Notifications') }}</h2>

      @if ($unread > 0)
        <form action="{{ route('notifications.readAll.update') }}" method="post" class="ml-auto" x-show="confirming" x-cloak>
          @csrf
          @method('PUT')

          <button
            type="submit"
            class="cursor-pointer rounded-md px-1.5 py-1 text-[12px] font-medium text-danger transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
          >
            {{ __('Yes, mark every one as read') }}
          </button>
        </form>

        <button
          type="button"
          @click="confirming = true"
          x-show="! confirming"
          class="ml-auto cursor-pointer rounded-md px-1.5 py-1 text-[12px] text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >
          {{ __('Mark every one as read') }}
        </button>
      @endif
    </div>

    @if ($notifications->isEmpty())
      <p class="px-3 py-6 text-center text-[13px] text-muted">{{ __('Nothing to catch up on.') }}</p>
    @else
      <ul class="max-h-96 divide-y divide-hairline overflow-y-auto">
        @foreach ($notifications as $notification)
          <li>
            <form action="{{ $notification['url'] }}" method="post">
              @csrf
              @method('PUT')

              <button
                type="submit"
                @class([
                  'flex w-full cursor-pointer flex-col items-start gap-0.5 px-3 py-2.5 text-left transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none',
                  'bg-card' => ! $notification['is_read'],
                ])
              >
                <span class="flex w-full items-center gap-1.5">
                  @unless ($notification['is_read'])
                    <span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-danger"></span>
                  @endunless

                  <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-ink">{{ $notification['title'] }}</span>

                  @unless ($notification['is_read'])
                    <span class="sr-only">{{ __('Not read yet.') }}</span>
                  @endunless
                </span>

                <span class="text-[12px] text-body">{{ $notification['body'] }}</span>

                <span class="text-[11px] text-muted-soft">{{ $notification['when'] }}</span>
              </button>
            </form>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
