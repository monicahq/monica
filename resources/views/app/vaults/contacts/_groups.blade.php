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
    <h2 class="erica-group">{{ $group['letter'] }}</h2>
  @endif

  @foreach ($group['people'] as $person)
    <x-row>
      <x-slot:leading>
        <x-avatar-initials :name="$person['name']" :size="40" />
      </x-slot:leading>

      <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="erica-row__title">{{ $person['name'] }}</span>

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
        <p class="erica-row__desc">{{ __('Known as :nickname', ['nickname' => $person['nickname']]) }}</p>
      @endif

      <x-slot:end>
        <span class="erica-small hidden w-36 truncate text-right lg:block">{{ $person['template'] }}</span>

        <div class="erica-row__meta">
          <b>{{ $person['when'] }}</b>

          <span>{{ $person['whenLabel'] }}</span>
        </div>
      </x-slot:end>
    </x-row>
  @endforeach
@endforeach
