{{-- The rows of the table of people, the favourites first under a heading of their own. --}}
{{-- @var \App\ViewModels\Vaults\ContactsViewModel $viewModel --}}
@php
  $width = count($viewModel->headings()) + 1;
@endphp
@foreach ($viewModel->groups() as $group)
  @if ($group['label'] !== null)
    <tr>
      <th scope="colgroup" colspan="{{ $width }}" class="px-4 pt-4 pb-1.5 text-left font-normal">
        <span class="flex items-center gap-2">
          <x-caption>{{ $group['label'] }}</x-caption>
          <span class="text-xs text-muted">{{ $group['count'] }}</span>
        </span>
      </th>
    </tr>
  @endif

  @foreach ($group['people'] as $person)
    <tr
      class="relative border-t border-hairline transition-colors duration-(--duration-fast) ease-out hover:bg-hover"
      :class="isSelected('{{ $person['uuid'] }}') && 'bg-hover'"
      @click="rowClick($event, '{{ $person['uuid'] }}')"
    >
      <td class="w-11 py-2.75 pl-4">
        <x-selection-checkbox
          :label="__('Select :name', ['name' => $person['name']])"
          data-contact-uuid="{{ $person['uuid'] }}"
          ::checked="isSelected('{{ $person['uuid'] }}')"
          @change="toggle('{{ $person['uuid'] }}')"
        />
      </td>

      <td class="px-4 py-2.75">
        <span class="flex min-w-0 items-center gap-3">
          <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="32" />

          <a href="{{ $person['url'] }}" data-turbo="true" class="truncate text-base font-semibold text-ink no-underline after:absolute after:inset-0 focus-ring-inset">{{ $person['name'] }}</a>

          @if ($person['showsStar'])
            <span class="flex shrink-0 text-ink-soft">
              <x-icon name="star" :size="14" :label="__('Favourite')" />
            </span>
          @endif

          @if ($person['isArchived'])
            <x-badge>{{ __('Archived') }}</x-badge>
          @endif
        </span>
      </td>

      @foreach ($person['cells'] as $cell)
        <td class="max-w-0 px-4 py-2.75 text-sm">
          @if ($cell['values'] === [])
            <span class="whitespace-nowrap text-muted">{{ $cell['whenEmpty'] }}</span>
          @else
            <div x-data="{ open: false }" class="flex min-w-0 flex-col gap-1">
              <span class="flex min-w-0 items-center gap-1.5">
                <span class="truncate text-body">{{ $cell['values'][0] }}</span>

                @if ($cell['moreLabel'] !== null)
                  <button
                    type="button"
                    @click="open = ! open"
                    :aria-expanded="open.toString()"
                    aria-expanded="false"
                    aria-label="{{ $cell['moreLabel'] }}"
                    class="relative z-10 flex-none cursor-pointer rounded-xs bg-card px-1.5 text-xs text-body focus-ring"
                  >+{{ count($cell['values']) - 1 }}</button>
                @endif
              </span>

              @if ($cell['moreLabel'] !== null)
                <span x-show="open" x-cloak class="flex flex-col gap-1">
                  @foreach (array_slice($cell['values'], 1) as $value)
                    <span class="truncate text-body">{{ $value }}</span>
                  @endforeach
                </span>
              @endif
            </div>
          @endif
        </td>
      @endforeach
    </tr>
  @endforeach
@endforeach
