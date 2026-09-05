{{-- One vault, opened: a box for every part of it. --}}
{{-- @var \App\ViewModels\Vaults\VaultDashboardViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $people = $viewModel->people();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="dashboard" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-1.5">
      <p class="text-sm text-pretty text-muted">{{ $viewModel->overline() }}</p>

      <h1 class="text-page-title text-balance text-ink">{{ $vault->name }}</h1>

      @if ($viewModel->description())
        <p class="max-w-[60ch] text-base text-pretty text-body">{{ $viewModel->description() }}</p>
      @endif
    </div>

    <x-button :href="$viewModel->newContactUrl()" data-turbo="true" size="small" class="max-sm:w-full">
      <x-slot:icon>
        <x-icon name="plus" :size="16" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Add someone') }}
    </x-button>
  </header>

  <div class="grid auto-rows-fr gap-x-5.5 gap-y-7 md:grid-cols-2 lg:grid-cols-3">
    <section class="flex min-w-0 flex-col gap-2.5">
      <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1 px-0.5">
        <h2 class="shrink-0 text-section-title text-ink">{{ __('Contacts') }}</h2>

        <p class="text-xs text-pretty text-muted">{{ $viewModel->peopleCaption() }}</p>
      </div>

      @if ($people === [])
        <div class="overflow-hidden rounded-lg border border-hairline bg-canvas flex flex-1 flex-col justify-center">
          <x-empty-state :title="__('Nobody here yet')">
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
        <div class="overflow-hidden rounded-lg border border-hairline bg-canvas flex flex-1 flex-col">
          @foreach ($people as $person)
            <a
              href="{{ $person['url'] }}"
              data-turbo="true"
              class="flex items-center gap-3 border-b border-hairline-soft px-4 py-2.5 transition-colors duration-[110ms] hover:bg-hover"
            >
              <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="34" />

              <span class="min-w-0 flex-1">
                <span class="block truncate text-base font-semibold text-ink">{{ $person['name'] }}</span>

                @if ($person['nickname'] !== '')
                  <span class="block truncate text-xs text-pretty text-muted">{{ $person['nickname'] }}</span>
                @endif
              </span>

              <x-icon name="chevron-right" :size="16" :stroke-width="1.6" class="shrink-0 text-muted" />
            </a>
          @endforeach

          <a
            href="{{ $viewModel->allContactsUrl() }}"
            data-turbo="true"
            class="mt-auto block px-4 py-3 text-sm text-pretty text-muted transition-colors duration-[110ms] hover:bg-hover hover:text-ink"
          >{{ $viewModel->allPeopleLabel() }}</a>
        </div>
      @endif
    </section>

    @foreach ($viewModel->unbuiltSections() as $section)
      <section class="flex min-w-0 flex-col gap-2.5">
        <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1 px-0.5">
          <h2 class="text-section-title shrink-0 text-ink">{{ $section['title'] }}</h2>

          <p class="text-xs text-pretty text-muted">{{ $section['caption'] }}</p>
        </div>

        <div class="overflow-hidden rounded-lg border border-hairline bg-canvas flex flex-1 flex-col justify-center">
          <x-empty-state :title="$section['emptyTitle']">
            <x-slot:icon>
              <x-icon :name="$section['icon']" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ $section['emptyText'] }}
          </x-empty-state>
        </div>
      </section>
    @endforeach

    <section class="flex min-w-0 flex-col gap-2.5">
      <h2 class="px-0.5 text-section-title text-ink-soft">{{ __('Vault settings') }}</h2>

      <a
        href="{{ $viewModel->settingsUrl() }}"
        data-turbo="true"
        class="overflow-hidden rounded-lg border border-hairline bg-canvas flex flex-1 flex-col transition-colors duration-[110ms] hover:bg-hover"
      >
        @foreach ($viewModel->settingsRows() as $row)
          <span class="block border-b border-hairline-soft px-4 py-3">
            <span class="block text-base font-semibold text-ink">{{ $row['title'] }}</span>

            <span class="block text-xs text-pretty text-muted">{{ $row['line'] }}</span>
          </span>
        @endforeach

        <span class="mt-auto block px-4 py-3 text-sm text-pretty text-muted">{{ __('All vault settings') }}</span>
      </a>
    </section>
  </div>
</x-shell>
