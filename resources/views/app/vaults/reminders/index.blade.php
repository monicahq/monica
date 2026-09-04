{{--
  Everything one vault will remind somebody about.

  The line under the title says how far ahead the list looks, and it is not a
  detail. Deadlines exist only inside the window they are generated for, so a
  yearly birthday eight months off has produced nothing yet and is genuinely not
  here. Stating the stretch of time is what stops a short list reading as a list
  that has lost things, and it is why the empty state below has two wordings
  rather than one.

  Each grouping is an address of its own, so one can be linked to and the back
  button moves between them. Clicking one swaps the list in place and pushes its
  address, rather than drawing the whole screen again. Which grouping somebody
  chose is not remembered between visits: it is a glance, not a preference.

  Both groupings share one row partial. A row that read differently depending on
  how the list was grouped would be two answers to one question.

  The tab beside this one lists what the vault has already sent. The two answer
  different questions rather than one narrowing the other, so each is an address
  of its own and the bar carries the count behind each.

  @var \App\ViewModels\Vaults\VaultRemindersViewModel $viewModel
--}}
@php
  $groups = $viewModel->groups();
  $byContact = $viewModel->groupBy() === \App\ViewModels\Vaults\VaultRemindersViewModel::GroupByContact;
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => $viewModel->vault()->name, 'url' => route('vaults.show', $viewModel->vault())],
        ['label' => __('Reminders')],
      ]"
    />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="reminders" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  @include('app.vaults.reminders._tabs', [
    'activeUrl' => $viewModel->activeUrl(),
    'sentUrl' => $viewModel->sentUrl(),
    'activeCount' => $viewModel->activeCount(),
    'sentCount' => $viewModel->sentCount(),
    'current' => 'active',
  ])

  @if ($groups === [])
    <x-panel>
      <x-empty-state :title="$viewModel->emptyTitle()">
        <x-slot:icon>
          <x-icon name="reminder" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ $viewModel->emptyLine() }}

        <x-slot:action>
          <x-button :href="$viewModel->peopleUrl()" data-turbo="true" size="small">{{ __('Open your people') }}</x-button>
        </x-slot:action>
      </x-empty-state>
    </x-panel>
  @else
    {{--
      The toggle swaps with the list, not beside it. Regrouping changes which of
      the two is the current one, so a swap that left the toggle alone would show
      a list grouped one way under a control claiming the other.
    --}}
    <div id="reminders-toggle" x-merge="replace" class="flex items-center gap-2">
      <span id="reminders-grouping" class="text-[12.5px] text-muted">{{ __('Grouped by') }}</span>

      <div class="flex items-center gap-1.5" role="group" aria-labelledby="reminders-grouping">
        @if ($byContact)
          <x-button.ghost :href="$viewModel->groupedByWhenUrl()" size="small" x-target.push="reminders-toggle reminders-list">{{ __('When it fires') }}</x-button.ghost>

          <x-button.secondary :href="$viewModel->groupedByContactUrl()" size="small" aria-current="true" x-target.push="reminders-toggle reminders-list">{{ __('Contact') }}</x-button.secondary>
        @else
          <x-button.secondary :href="$viewModel->groupedByWhenUrl()" size="small" aria-current="true" x-target.push="reminders-toggle reminders-list">{{ __('When it fires') }}</x-button.secondary>

          <x-button.ghost :href="$viewModel->groupedByContactUrl()" size="small" x-target.push="reminders-toggle reminders-list">{{ __('Contact') }}</x-button.ghost>
        @endif
      </div>
    </div>

    {{--
      The whole list is one region and regrouping replaces it, rather than each
      group being its own: regrouping changes how many groups there are and what
      each is called, so swapping them one by one would leave a list that
      disagrees with itself.
    --}}
    <div id="reminders-list" x-merge="replace" class="flex flex-col gap-9 transition-opacity [&[aria-busy]]:opacity-60">
      @foreach ($groups as $group)
        <section
          @class([
            'relative flex flex-col gap-3.5 rounded-xl px-5 pt-4.5 pb-5.5',
            'border border-warning-edge bg-warning-tint' => $group['isOverdue'],
          ])
        >
          <h2 class="flex items-baseline gap-2.5">
            @if ($group['isOverdue'])
              <x-icon name="clock" :size="14" :stroke-width="1.6" class="shrink-0 self-center text-warning" />
            @endif

            <span
              @class([
                'text-[11px] font-semibold tracking-caption uppercase',
                'text-warning' => $group['isOverdue'],
                'text-muted' => ! $group['isOverdue'],
              ])
            >{{ $group['title'] }}</span>

            <span class="text-[12.5px] text-muted-soft">{{ $group['count'] }}</span>
          </h2>

          <div class="flex flex-col gap-2.5">
            @foreach ($group['rows'] as $row)
              @include('app.vaults.reminders.row', ['row' => $row])
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  @endif
</x-shell>
