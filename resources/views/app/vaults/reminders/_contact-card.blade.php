{{-- Who the reminder is about, at the head of its screen. --}}
{{--
  @var array<string, mixed> $contact
  @var string $builtFrom
--}}
<div class="flex items-start gap-4 rounded-lg border border-hairline bg-canvas p-4 shadow-panel">
  <x-contact-avatar :name="$contact['name']" :url="$contact['avatarUrl']" :size="52" />

  <div class="flex min-w-0 flex-1 flex-col gap-1.5">
    <a
      href="{{ $contact['url'] }}"
      data-turbo="true"
      class="text-card-title text-ink hover:underline"
    >{{ $contact['name'] }}</a>

    <p class="text-sm text-pretty text-body">{{ $builtFrom }}</p>

    <p class="text-xs text-muted">{{ $contact['reminderCountLine'] }}</p>
  </div>

  <x-button.secondary :href="$contact['url']" data-turbo="true" size="small">{{ __('Open the record') }}</x-button.secondary>
</div>
