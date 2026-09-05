{{--
  Who the reminder is about, at the head of its screen.

  It carries the name, the face, where the date is read from and how many other
  reminders the same person has. The design asked for a group, a place and the
  last conversation as well; this product has none of the three, and a card that
  invented them would be a card that lies about what is stored.

  The count is the reason the card is worth its space: somebody arriving from a
  list of deadlines wants to know whether this person is reminded about once or
  five times, and that is not on the list.

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
