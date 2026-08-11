{{--
  One vault, opened: a box for every part of it.

  The screen is a map rather than a summary. Six boxes, each in the colour that
  part of the product answers to, each the way into it. Two of them are real, the
  people and the settings, and the other four are drawn as what will land in them:
  the journal, the tasks, the reminders and the files are each their own model and
  none of them has been written. Those four are not links. A word that does not
  move is better than a link that leads nowhere.

  Three things the design asks for are not here. The star beside the title marks a
  favourite vault, which is not something a vault can be. "Write today" opens the
  journal. The search field in the bar reads every field of every record, which
  needs an index that does not exist, and the launcher behind the dock already
  answers to the same keystroke.

  @var \App\ViewModels\Vaults\VaultDashboardViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
  $people = $viewModel->people();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:bar>
    <a href="{{ $viewModel->vaultsUrl() }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />

      {{ __('Your vaults') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" :vault="$vault" current="dashboard" />
  </x-slot:dock>

  <header class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-1.5">
      <p class="erica-small">{{ $viewModel->overline() }}</p>

      <h1 class="erica-page-title">{{ $vault->name }}</h1>

      @if ($viewModel->description())
        <p class="erica-lede max-w-[60ch]">{{ $viewModel->description() }}</p>
      @endif
    </div>

    <x-button :href="$viewModel->newContactUrl()" data-turbo="true" size="small" class="max-sm:w-full">
      <x-slot:icon>
        <x-icon name="plus" :size="16" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Add someone') }}
    </x-button>
  </header>

  <div class="grid auto-rows-fr gap-x-5 gap-y-7 md:grid-cols-2 lg:grid-cols-3">
    <section class="flex min-w-0 flex-col gap-2.5">
      <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1 px-0.5">
        <h2 class="erica-section-title shrink-0 text-terracotta">{{ __('Contacts') }}</h2>

        <p class="erica-caption">{{ $viewModel->peopleCaption() }}</p>
      </div>

      @if ($people === [])
        <div class="erica-panel flex flex-1 flex-col justify-center">
          <x-empty-state :title="__('Nobody here yet')" hue="var(--terracotta)" tint="var(--terracotta-tint)">
            <x-slot:icon>
              <x-icon name="people" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('The people this part of your life is made of. Everything else on this screen hangs off them.') }}

            <x-slot:action>
              <x-button :href="$viewModel->newContactUrl()" data-turbo="true" size="small">
                {{ __('Add the first person') }}
              </x-button>
            </x-slot:action>
          </x-empty-state>
        </div>
      @else
        {{--
          The box is the link, all of it. A person has no page of their own yet,
          so a row inside it would be a second link to the same list.
        --}}
        <a
          href="{{ $viewModel->allContactsUrl() }}"
          data-turbo="true"
          class="erica-panel flex flex-1 flex-col transition-colors duration-[110ms] hover:bg-hover"
        >
          @foreach ($people as $person)
            <span class="flex items-center gap-3 border-b border-hairline-soft px-4 py-2.5">
              <x-avatar-initials :name="$person['name']" :size="34" />

              <span class="min-w-0 flex-1">
                <span class="erica-row__title block truncate">{{ $person['name'] }}</span>

                @if ($person['nickname'] !== '')
                  <span class="erica-caption block truncate">{{ $person['nickname'] }}</span>
                @endif
              </span>
            </span>
          @endforeach

          <span class="erica-small mt-auto block px-4 py-3">{{ $viewModel->allPeopleLabel() }}</span>
        </a>
      @endif
    </section>

    @foreach ($viewModel->unbuiltSections() as $section)
      <section class="flex min-w-0 flex-col gap-2.5">
        <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1 px-0.5">
          <h2 class="erica-section-title shrink-0" style="color: {{ $section['hue'] }}">{{ $section['title'] }}</h2>

          <p class="erica-caption">{{ $section['caption'] }}</p>
        </div>

        <div class="erica-panel flex flex-1 flex-col justify-center">
          <x-empty-state :title="$section['emptyTitle']" :hue="$section['hue']" :tint="$section['tint']">
            <x-slot:icon>
              <x-icon :name="$section['icon']" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ $section['emptyText'] }}
          </x-empty-state>
        </div>
      </section>
    @endforeach

    <section class="flex min-w-0 flex-col gap-2.5">
      {{-- The only heading with no hue: the settings are not a kind of thing the
           vault holds, they are the vault itself. --}}
      <h2 class="erica-section-title px-0.5">{{ __('Vault settings') }}</h2>

      <a
        href="{{ $viewModel->settingsUrl() }}"
        data-turbo="true"
        class="erica-panel flex flex-1 flex-col transition-colors duration-[110ms] hover:bg-hover"
      >
        @foreach ($viewModel->settingsRows() as $row)
          <span class="block border-b border-hairline-soft px-4 py-3">
            <span class="erica-row__title block">{{ $row['title'] }}</span>

            <span class="erica-caption block">{{ $row['line'] }}</span>
          </span>
        @endforeach

        <span class="erica-small mt-auto block px-4 py-3">{{ __('All vault settings') }}</span>
      </a>
    </section>
  </div>
</x-shell>
