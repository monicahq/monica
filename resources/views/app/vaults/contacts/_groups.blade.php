{{--
  The rows of the list, in runs under the letter they begin with.

  It is its own file because the next page is appended to the one before it: what
  comes back over ajax is this fragment again, starting wherever the last page
  stopped, and a run that was cut in half by the pagination comes back without a
  heading so it reads as the rest of itself.

  A run with no letter is drawn without a heading. That is the continuation above,
  and every run of a list ordered by something a letter says nothing about.

  @var \App\ViewModels\Vaults\ContactsViewModel $viewModel
--}}
@foreach ($viewModel->groups() as $group)
  @if ($group['letter'] !== null)
    {{--
      The letter sits on the inset shade rather than on the canvas the rows use, so
      it reads as a divider between two runs and not as a row with one word in it.
    --}}
    <h2 class="flex items-center gap-2.5 border-b border-hairline bg-sand px-4.5 py-2.25 uppercase text-xs tracking-caption text-muted">{{ $group['letter'] }}</h2>
  @endif

  @foreach ($group['people'] as $person)
    <x-row>
      <x-slot:leading>
        <x-avatar-initials :name="$person['name']" :size="40" />
      </x-slot:leading>

      <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="text-base font-semibold text-ink">{{ $person['name'] }}</span>

        @if ($person['isFavourite'])
          <span class="flex shrink-0 text-warning">
            <x-icon name="star" :size="14" :label="__('Favourite')" />
          </span>
        @endif

        @if ($person['isArchived'])
          <x-badge>{{ __('Archived') }}</x-badge>
        @endif
      </p>

      @if ($person['nickname'])
        <p class="mt-px text-sm text-pretty text-muted">{{ __('Known as :nickname', ['nickname' => $person['nickname']]) }}</p>
      @endif

      <x-slot:end>
        <span class="hidden w-36 truncate text-right text-sm text-pretty text-muted lg:block">{{ $person['template'] }}</span>

        <div class="text-right">
          <b class="block text-sm font-semibold whitespace-nowrap text-ink">{{ $person['when'] }}</b>

          <span class="block text-xs whitespace-nowrap text-muted">{{ $person['whenLabel'] }}</span>
        </div>
      </x-slot:end>
    </x-row>
  @endforeach
@endforeach
