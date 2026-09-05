{{--
  One reminder on the list: when it fires, who it is about, and what it says.

  It is a deadline rather than a rule. Three of the four things somebody will be
  able to do to it act on one concrete instant, so the row has to be that instant,
  and the date shown is the one it is due at now rather than the one it was
  generated for: somebody who put a reminder off wants to see where they moved it
  to.

  The stamp on the left is dashed and slightly torn at the corner. It is the one
  piece of deliberate imperfection on this screen, and a list of dates is where it
  earns its keep: the eye finds a date faster on something shaped like a date than
  on a line of text.

  Lateness is said three ways and never by colour alone: the stripe changes, the
  words change from "in two days" to "four days ago", and the sentence behind the
  date says "Sent" rather than "Will be sent".

  The relative date is focusable, so the exact instant is one Tab away and not
  only one hover away. It names the rule's own zone, because that is the zone the
  reminder fires in, which is not necessarily the reader's.

  Three controls: put it off, be done with it, and send it now to see what it
  looks like. Each is icon only and each carries its own name, said to a screen
  reader and shown on hover and on focus, since a row of unlabelled glyphs is a
  row nobody can use.

  The fourth control opens the rule behind the deadline, where the cadence, the
  moments and the zone are explained. It is a link rather than a button, so the
  middle click and the context menu work on it the way they work on the person's
  name above. It is left out on the reminder's own screen, where a link to the
  page somebody is reading says nothing, and on a deadline whose rule has gone:
  history outlives the rule that made it, and a link to a page that answers 404
  is worse than no link.

  Being done with a reminder asks nothing first. It closes one deadline, leaves
  the rule producing and keeps the row, so there is nothing to warn about.

  @var array<string, mixed> $row
  @var bool $linkToReminder  false on the reminder's own screen, which is where this row is already
--}}
<div
  @class([
    'relative flex items-stretch rounded-lg border border-hairline bg-canvas shadow-panel transition-colors duration-(--duration-fast) ease-out hover:border-hairline-strong',
  ])
>
  {{-- The torn corner and the stripe are decoration, and neither carries meaning
       on its own: the stripe agrees with words that already say the same thing.

       They are clipped by this layer rather than by the card, because the card
       also holds the tooltips, and a card that clipped its own children cut
       every bubble off at its edge. --}}
  <span aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden rounded-lg">
    <span class="absolute inset-x-0 top-0 h-6.5 bg-hairline/40 [clip-path:polygon(0_0,100%_0,50%_100%)]"></span>

    <span
      @class([
        'absolute inset-y-0 left-0 w-1',
        'bg-[repeating-linear-gradient(135deg,var(--color-warning)_0_6px,transparent_6px_12px)]' => $row['isLate'],
        'bg-[repeating-linear-gradient(135deg,var(--color-hairline-strong)_0_6px,transparent_6px_12px)]' => ! $row['isLate'],
      ])
    ></span>
  </span>

  <div class="relative flex flex-none flex-col items-center gap-1 py-2.75 pl-3.5">
    <span class="flex w-15.5 flex-col items-center rounded border-2 border-dashed border-hairline-strong px-0.75 py-1">
      <span class="text-[9px] font-semibold tracking-[0.1em] text-muted uppercase">{{ $row['month'] }}</span>
      <span class="text-[19px] leading-tight font-semibold text-ink">{{ $row['day'] }}</span>
      <span class="text-[9px] tracking-[0.06em] text-muted-soft">{{ $row['year'] }}</span>
    </span>

    @if ($row['cadence'] !== '')
      <span class="flex items-center gap-1">
        <x-icon :name="$row['cadenceIcon']" :size="11" :stroke-width="1.5" class="shrink-0 text-muted-soft" />
        <span class="text-[10px] text-muted">{{ $row['cadence'] }}</span>
      </span>
    @endif
  </div>

  <div class="relative flex min-w-0 flex-1 flex-wrap items-center gap-x-3.5 gap-y-2.5 py-3 pr-3.5 pl-4">
    {{-- The name and the text take the whole width on a narrow screen, which
         drops the date and the controls onto a line of their own. Without that
         the three of them share one line at any width and run into each other
         on a phone. --}}
    <div class="flex min-w-0 flex-1 basis-full flex-col gap-1.5 sm:basis-0">
      <div class="flex min-w-0 items-center gap-2.25">
        @if ($row['contactName'] !== null)
          <x-contact-avatar :name="$row['contactName']" :url="$row['avatarUrl']" :size="26" />

          <a
            href="{{ $row['contactUrl'] }}"
            data-turbo="true"
            class="truncate text-[13.5px] font-semibold text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:text-link"
          >{{ $row['contactName'] }}</a>
        @else
          <span class="text-[13.5px] font-semibold text-muted">{{ __('Somebody who is gone') }}</span>
        @endif
      </div>

      <div class="flex min-w-0 items-start gap-2 border-t border-dashed border-hairline pt-1.5">
        <x-icon :name="$row['icon']" :size="15" :stroke-width="1.5" class="mt-px shrink-0 text-muted" />

        <span class="min-w-0 text-[13.5px] leading-snug text-pretty text-body">{{ $row['text'] }}</span>
      </div>
    </div>

    <x-tooltip :text="$row['exact']" :focusable="true" class="flex-none max-sm:ml-auto">
      <span
        @class([
          'border-b border-dotted border-hairline-strong text-xs whitespace-nowrap',
          'text-warning' => $row['isLate'],
          'text-muted' => ! $row['isLate'],
        ])
      >{{ $row['relative'] }}</span>
    </x-tooltip>

    <div class="flex flex-none items-center gap-1.5">
      @if (($linkToReminder ?? true) && $row['reminderUrl'] !== null)
        <x-icon-button
          :href="$row['reminderUrl']"
          data-turbo="true"
          icon="reminder"
          :label="__('Open this reminder')"
          align="right"
        />
      @endif

      @include('app.vaults.reminders._postpone', ['row' => $row])

      <x-form method="post" :action="$row['testUrl']" class="contents">
        <x-icon-button type="submit" icon="mail" :label="__('Send it to me now')" align="right" />
      </x-form>

      <x-form method="put" :action="$row['dismissUrl']" class="contents">
        <x-icon-button type="submit" icon="check" :label="__('I am done with this one')" align="right" />
      </x-form>
    </div>
  </div>
</div>
