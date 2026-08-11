{{--
  One person, on one row of the list.

  Every cell but the name can be turned off from the picker in the bar, so each
  one asks the component above the table whether it is being drawn. The name
  cannot: a row of dates with nobody attached to them is not a row.

  The name is not a link. The page of one contact is a later screen, and the
  navigation already settles that a word which does not move is better than a
  link that leads nowhere.

  @var array{name: string, isFavourite: bool, nickname: string, lastSpoke: string, template: string, added: string} $row
--}}
@php
  $cell = 'border-b border-hairline-soft px-3.5 py-2.5 align-middle whitespace-nowrap';
@endphp

<tr class="transition-colors duration-150 hover:bg-hover">
  <td class="{{ $cell }}">
    <span class="flex min-w-0 items-center gap-2.5">
      <x-avatar-initials :name="$row['name']" :size="26" />

      <span class="truncate text-sm font-semibold text-ink">{{ $row['name'] }}</span>

      @if ($row['isFavourite'])
        <span class="shrink-0 text-warning" title="{{ __('Favourite') }}">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 1.8 9.9 5.7l4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"></path>
          </svg>
        </span>
      @endif
    </span>
  </td>

  <td x-show="shown('nickname')" class="{{ $cell }} text-sm text-body">{{ $row['nickname'] }}</td>

  <td x-show="shown('last-spoke')" class="{{ $cell }} text-right text-sm text-muted">{{ $row['lastSpoke'] }}</td>

  <td x-show="shown('template')" class="{{ $cell }} text-sm text-body">{{ $row['template'] }}</td>

  <td x-show="shown('added')" class="{{ $cell }} text-right text-sm text-muted">{{ $row['added'] }}</td>
</tr>
