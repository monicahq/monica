{{--
  What the application says back after a save: a card in the bottom right corner
  that announces itself, counts down, and leaves on its own. It is fed by the
  session, so any controller that redirects with a `status` (or an `error`) gets
  one for free, whether the form was submitted over ajax or not.

  The region is in the page even when there is nothing to say, so a screen reader
  is already watching it by the time a message arrives.
--}}
@php
  $toasts = collect([
      [
          'title' => session('status'),
          'description' => session('status_description'),
          'icon' => 'check',
          'tint' => 'bg-success/10 text-success',
          'bar' => 'bg-success/40',
      ],
      [
          'title' => session('error'),
          'description' => session('error_description'),
          'icon' => 'alert',
          'tint' => 'bg-error/10 text-error',
          'bar' => 'bg-error/40',
      ],
  ])->filter(fn (array $toast): bool => filled($toast['title']));
@endphp

<div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-end p-4 sm:p-6" role="status" aria-live="polite">
  {{--
    Replace instead of morphing: the merge strategy is morph everywhere else, and
    morphing would patch a toast that has already hidden itself back into place
    without re-running x-init, so its timer would never start again. Replacing
    builds a fresh component on every response.
  --}}
  <div x-sync x-merge="replace" id="notifications" class="pointer-events-auto flex w-full max-w-sm flex-col gap-3">
    @foreach ($toasts as $toast)
      <div
        x-data="{ shown: true, leaving: false, dismiss() { this.leaving = true; setTimeout(() => this.shown = false, 200) } }"
        x-init="setTimeout(() => dismiss(), 4500)"
        x-show="shown"
        :class="leaving ? 'animate-toast-out' : 'animate-toast-in'"
        class="relative flex items-start gap-3 overflow-hidden rounded-xl border border-hairline bg-canvas p-3.5 shadow-lg"
      >
        <div class="relative flex size-8 shrink-0 items-center justify-center rounded-full {{ $toast['tint'] }}">
          <span class="animate-toast-ring absolute inset-0 rounded-full {{ $toast['tint'] }}" aria-hidden="true"></span>

          @if ($toast['icon'] === 'check')
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative" aria-hidden="true">
              <path class="animate-toast-tick" d="M3.6 8.4 6.4 11.2 12.4 5.2" stroke-dasharray="22"></path>
            </svg>
          @else
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="relative" aria-hidden="true">
              <circle cx="8" cy="8" r="6"></circle>
              <line x1="8" y1="4.8" x2="8" y2="8.8"></line>
              <circle cx="8" cy="11.2" r="0.5" fill="currentColor" stroke="none"></circle>
            </svg>
          @endif
        </div>

        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold tracking-tight text-ink">{{ $toast['title'] }}</p>

          @if ($toast['description'])
            <p class="mt-0.5 text-sm leading-snug text-body">{{ $toast['description'] }}</p>
          @endif
        </div>

        <button
          type="button"
          x-on:click="dismiss()"
          aria-label="{{ __('Dismiss') }}"
          class="flex size-6 shrink-0 items-center justify-center rounded-md text-placeholder transition-colors hover:bg-hover hover:text-ink"
        >
          <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line>
            <line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line>
          </svg>
        </button>

        <span x-show="! leaving" class="animate-toast-timer absolute inset-x-0 bottom-0 h-0.5 origin-left {{ $toast['bar'] }}" aria-hidden="true"></span>
      </div>
    @endforeach
  </div>
</div>
