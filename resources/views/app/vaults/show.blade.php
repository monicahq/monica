{{--
  One vault, opened: what is coming up, what you said you would do, what you
  last wrote, and who you last looked at.

  Everything below the title is invented. Reminders, tasks, the journal and the
  record of who was opened recently are each their own model and none of them
  exists yet, so the rows are there to show the shape of the screen and not to
  be believed. The view model says which is which.

  @var \App\ViewModels\Vaults\VaultDashboardViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
@endphp
<x-app-layout :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-vault.sidebar
      :vault="$vault"
      :vaults="$viewModel->vaults()"
      :user="$viewModel->user()"
      current="dashboard"
      :task-count="\App\ViewModels\Vaults\VaultDashboardViewModel::PRETEND_OPEN_TASKS"
    />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => __('Your vaults'), 'url' => $viewModel->vaultsUrl()],
        ['label' => $vault->name],
      ]"
    />
  </x-slot:breadcrumb>

  <x-slot:actions>
    {{--
      Search reads every field of every record, which is a later model. The
      field is here because the bar is built around it, and it says plainly
      that it cannot be used yet rather than swallowing what somebody types.
    --}}
    <input
      type="search"
      disabled
      placeholder="{{ __('Search this vault') }}"
      aria-label="{{ __('Search this vault') }}"
      title="{{ __('Searching arrives with the search index.') }}"
      class="hidden w-55 rounded-md border border-hairline-strong bg-input px-2.5 py-1.5 text-[13px] text-ink placeholder-placeholder opacity-60 md:block"
    />

    <x-button :href="$viewModel->newContactUrl()" size="small" data-turbo="true">{{ __('Add contact') }}</x-button>
  </x-slot:actions>

  <div class="flex flex-wrap items-start justify-between gap-6">
    <div class="max-w-140 space-y-1.5">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $vault->name }}</h1>

      @if ($viewModel->description())
        <p class="text-sm text-body">{{ $viewModel->description() }}</p>
      @endif
    </div>

    <div class="flex items-center gap-5">
      @foreach ($viewModel->stats() as $stat)
        <div>
          <p class="text-lg font-semibold text-ink">{{ $stat['value'] }}</p>

          <p class="text-xs text-muted-soft">{{ $stat['label'] }}</p>
        </div>
      @endforeach
    </div>
  </div>

  <div class="space-y-2">
    <div class="flex items-baseline justify-between gap-4">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Coming up') }}</h2>

        <p class="text-sm text-muted">{{ __('Dates in the next thirty days, from birthdays and anything you asked to be reminded of.') }}</p>
      </div>

      <span class="shrink-0 text-[13px] text-muted-soft">{{ __('All reminders') }}</span>
    </div>

    <ul class="overflow-hidden rounded-xl border border-hairline bg-canvas">
      @foreach ($viewModel->reminders() as $reminder)
        <li class="flex items-center gap-3 px-4 py-3 transition-colors duration-150 not-first:border-t not-first:border-hairline-soft hover:bg-hover">
          <x-avatar-initials :name="$reminder['person']" :size="28" />

          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-ink">{{ $reminder['person'] }}</p>

            <p class="truncate text-[13px] text-muted">{{ $reminder['what'] }}</p>
          </div>

          <div class="ml-auto shrink-0 text-right">
            <p class="text-[13px] font-semibold text-ink">{{ $reminder['when'] }}</p>

            <p class="text-xs text-muted-soft">{{ $reminder['relative'] }}</p>
          </div>
        </li>
      @endforeach
    </ul>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-2 self-start">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Tasks') }}</h2>

        <p class="text-sm text-muted">{{ __('Things you said you would do for someone in this vault.') }}</p>
      </div>

      <div class="overflow-hidden rounded-xl border border-hairline bg-canvas">
        <ul>
          @foreach ($viewModel->tasks() as $task)
            <li class="flex items-start gap-2.5 px-4 py-3 transition-colors duration-150 not-first:border-t not-first:border-hairline-soft hover:bg-hover">
              <span
                class="mt-px flex size-4.5 shrink-0 items-center justify-center rounded-sm border {{ $task['done'] ? 'border-primary bg-primary text-on-primary' : 'border-hairline-strong bg-input text-transparent' }}"
                aria-hidden="true"
              >
                <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
                </svg>
              </span>

              <div class="min-w-0 flex-1">
                <p class="text-sm {{ $task['done'] ? 'text-muted line-through' : 'text-ink' }}">{{ $task['title'] }}</p>

                <p class="text-xs text-muted-soft">{{ $task['meta'] }}</p>
              </div>
            </li>
          @endforeach
        </ul>

        <p class="border-t border-hairline-soft px-4 py-2.5 text-[13px] text-muted-soft">{{ __('Add a task') }}</p>
      </div>
    </div>

    <div class="space-y-2 self-start">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Last written') }}</h2>

        <p class="text-sm text-muted">{{ __('The most recent entries in the journal of this vault.') }}</p>
      </div>

      <div class="overflow-hidden rounded-xl border border-hairline bg-canvas">
        <ul>
          @foreach ($viewModel->entries() as $entry)
            <li class="space-y-1 px-4 py-3 transition-colors duration-150 not-first:border-t not-first:border-hairline-soft hover:bg-hover">
              <p class="flex items-baseline gap-2">
                <span class="text-sm font-semibold text-ink">{{ $entry['title'] }}</span>

                <span class="ml-auto shrink-0 text-xs text-muted-soft">{{ $entry['date'] }}</span>
              </p>

              <p class="line-clamp-2 text-[13px] leading-relaxed text-muted">{{ $entry['excerpt'] }}</p>
            </li>
          @endforeach
        </ul>

        <p class="border-t border-hairline-soft px-4 py-2.5 text-[13px] text-muted-soft">{{ __('Write an entry') }}</p>
      </div>
    </div>
  </div>

  <div class="space-y-2">
    <div class="flex items-baseline justify-between gap-4">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Recently opened') }}</h2>

        <p class="text-sm text-muted">{{ __('The people you looked at last, in case you were in the middle of something.') }}</p>
      </div>

      <span class="shrink-0 text-[13px] text-muted-soft">{{ __('All contacts') }}</span>
    </div>

    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($viewModel->recentlyOpened() as $person)
        <li class="flex items-center gap-2.5 rounded-lg border border-hairline bg-canvas p-3">
          <x-avatar-initials :name="$person['name']" :size="32" />

          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-ink">{{ $person['name'] }}</p>

            <p class="truncate text-xs text-muted-soft">{{ $person['meta'] }}</p>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</x-app-layout>
