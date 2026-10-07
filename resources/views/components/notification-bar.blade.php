{{-- The bar in the top of every signed in screen: what the application has told you, newest first. --}}
{{-- @var \App\ViewModels\NotificationBarViewModel|null $viewModel --}}
@php
  $unread = $viewModel?->unreadCount() ?? 0;
@endphp

<div x-data="{ open: false, confirming: false, loaded: false }" @keydown.escape.window="open = false; confirming = false" class="relative">
  <button
    type="button"
    @click="open = ! open; confirming = false; if (open && ! loaded) { loaded = true; $ajax('{{ route('notifications.index') }}', { target: 'notification-list', focus: false }) }"
    @click.outside="open = false; confirming = false"
    ::aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="dialog"
    aria-controls="notification-bar-panel"
    aria-label="{{ trans_choice('Notifications, :count unread|Notifications, :count unread', $unread) }}"
    class="relative flex h-9 cursor-pointer items-center gap-1.75 rounded-md px-2.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
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
      <h2 id="notification-bar-heading" class="text-sm font-semibold text-ink">{{ __('Notifications') }}</h2>

      @if ($unread > 0)
        <form action="{{ route('notifications.readAll.update') }}" method="post" class="ml-auto" x-show="confirming" x-cloak>
          @csrf
          @method('PUT')

          <button
            type="submit"
            class="cursor-pointer rounded-md px-1.5 py-1 text-xs font-medium text-danger transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
          >
            {{ __('Yes, mark every one as read') }}
          </button>
        </form>

        <button
          type="button"
          @click="confirming = true"
          x-show="! confirming"
          class="ml-auto cursor-pointer rounded-md px-1.5 py-1 text-xs text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
        >
          {{ __('Mark every one as read') }}
        </button>
      @endif
    </div>

    <div id="notification-list" x-merge="replace" class="dim-when-busy">
      <p class="px-3 py-6 text-center text-sm text-muted">{{ __('Loading…') }}</p>
    </div>
  </div>
</div>
