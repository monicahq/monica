{{--
  One person, and everything written down about them.

  The name is set at display size and is the only thing on the screen that is,
  because the whole page is about one person and saying so once is enough. The
  facts beside the photograph are the three you would want before picking up the
  phone; everything longer waits for a panel.

  The tabs are the pages of the template the contact renders from, with an
  overview in front of them that belongs to no template. The overview is the
  same on everybody: what you have written about them down one column, what you
  know about them down the other.

  Half of what is drawn here has no model behind it. Those panels are drawn
  rather than left out, because the shape of the page is what is being decided,
  and each of them says what will land in it. The controls that would write any
  of it are drawn and not offered, the way the dock draws a destination that
  does not exist: a button that comes back doing nothing is worse than one that
  visibly does not move yet.

  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
  $unbuilt = $viewModel->unbuilt();

  /* Drawn and not offered. It keeps the shape of the control it will become. */
  $inert = 'inline-flex h-8.5 cursor-default items-center gap-2 rounded-md border border-hairline px-3.25 '
    .'font-sans text-[13.5px] font-semibold text-placeholder';
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:bar>
    <x-vault.bar
      :vault="$vault"
      :items="[
        ['label' => __('People'), 'icon' => 'people', 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->title()],
      ]"
    />
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" :vault="$vault" current="people" />
  </x-slot:dock>

  {{--
    The way back is a plain anchor rather than <x-link>, which is for a link
    inside running text and arrives underlined and in the link colour.
  --}}
  <a
    href="{{ $viewModel->peopleUrl() }}"
    data-turbo="true"
    class="flex w-fit items-center gap-1.75 text-sm text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink"
  >
    <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
    {{ __('All people') }}
  </a>

  <header class="flex flex-wrap items-start gap-5.5">
    <div class="relative shrink-0">
      <x-avatar-initials :name="$viewModel->title()" :size="104" />

      @if ($viewModel->isFavourite())
        <span
          class="absolute -right-1.5 -bottom-1.5 flex size-8 items-center justify-center rounded-full bg-marigold-tint text-marigold-deep ring-3 ring-page"
          aria-hidden="true"
        >
          <x-icon name="star" :size="17" :stroke-width="1.6" />
        </span>
      @endif
    </div>

    <div class="min-w-60 flex-1">
      <h1 class="font-display text-display-2 text-balance text-ink">{{ $viewModel->title() }}</h1>

      <p class="mt-2 text-base text-body">
        {{ $viewModel->relationshipLine() }}

        @if ($viewModel->nickname())
          {{ __('Known as :nickname.', ['nickname' => $viewModel->nickname()]) }}
        @endif

        @if ($viewModel->isArchived())
          <x-badge class="ml-1 align-middle">{{ __('Archived') }}</x-badge>
        @endif
      </p>

      {{--
        A region of its own, because the facts beside the photograph are fields
        from the template and saving the block one of them sits in has to change
        it here too. Its id is what the block's form aims at alongside itself.
      --}}
      <ul id="contact-facts" x-merge="replace" class="mt-4 flex list-none flex-wrap gap-x-5.5 gap-y-2">
        @foreach ($viewModel->headerFacts() as $fact)
          <li class="flex items-center gap-1.75 text-sm text-body">
            <x-icon :name="$fact['icon']" :size="16" :stroke-width="1.5" class="{{ $fact['iconClass'] }}" />
            {{ $fact['label'] }} <b class="font-semibold text-ink">{{ $fact['value'] }}</b>
          </li>
        @endforeach
      </ul>
    </div>

    {{--
      One way in rather than a button for each thing that can be done to the row.
      The page is about a person, and a header of three controls was a header
      about the record: two of them were drawn and not offered, and the third was
      the name.
    --}}
    <div class="ml-auto flex flex-wrap items-center gap-2.5">
      <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">
        <x-icon name="profile" :size="15" :stroke-width="1.6" />
        {{ __('Edit contact') }}
      </x-button.secondary>
    </div>
  </header>

  {{--
    The pages of the record. The rule runs the whole width and the tab you are on
    thickens its own piece of it in terracotta, which is the one place on this
    screen the accent is used.
  --}}
  <div class="flex flex-wrap items-center gap-3.5 border-b border-hairline">
    <div role="tablist" class="-mb-px flex flex-wrap items-end gap-1">
      @foreach ($viewModel->pages() as $page)
        <a
          href="{{ $page['url'] }}"
          data-turbo="true"
          role="tab"
          aria-selected="{{ $page['isSelected'] ? 'true' : 'false' }}"
          @class([
            'flex h-10 flex-none items-center gap-2 border-b-2 px-3.5 text-base font-semibold no-underline',
            'transition-[color,border-color] duration-(--duration-fast) ease-out hover:text-ink',
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
            $page['isSelected'] ? 'border-b-primary text-ink' : 'border-transparent text-muted',
          ])
        >
          <x-icon :name="$page['icon']" :size="16" :stroke-width="1.6" />
          {{ $page['label'] }}

          @if ($page['count'] !== null)
            <span class="text-xs font-normal text-placeholder">{{ $page['count'] }}</span>
          @endif
        </a>
      @endforeach
    </div>

    {{--
      Only offered when there is something to see. A link to a screen that says
      "nothing here" is a question the page raises for no reason.

      Which template the record renders from used to be named here too. It is not
      any more: it is the structure of the page rather than anything about the
      person, and a line of machinery above every record earns its place only on
      the screen where the machinery is changed.
    --}}
    @if ($viewModel->hasValuesSetAside())
      <a
        href="{{ $viewModel->setAsideUrl() }}"
        data-turbo="true"
        class="ml-auto flex items-center gap-2 pb-2 text-xs text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink"
      >{{ __('Set aside') }}</a>
    @endif
  </div>

  {{--
    A newer version of the template is waiting. It sits above the tabs' content
    rather than on one of them, because which version the record renders from is
    a fact about the whole record, and it is the one line of machinery on this
    screen that earns its place: nothing else would say a template has changed.

    It offers the one thing there is to do. There is nothing to dismiss, because
    the banner goes the moment it is applied, and nothing to preview, because
    what changed between two versions is not something the product can yet say.
  --}}
  @if ($update = $viewModel->templateUpdate())
    <x-notice tone="info" icon="file" :title="$update['title']">
      {{ $update['body'] }}

      <x-slot:action>
        <x-form method="put" :action="$update['applyUrl']">
          <x-button size="small">{{ $update['label'] }}</x-button>
        </x-form>
      </x-slot:action>
    </x-notice>
  @endif

  @if ($viewModel->onOverview())
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
      <div class="flex flex-col gap-5">
        <x-panel :title="__('Write something down')" icon="note" icon-class="bg-indigo-tint text-indigo">
          <x-textarea
            id="entry"
            :placeholder="__('A call, a visit, something they said.')"
            :rows="3"
            disabled
          />

          <div class="mt-3 flex items-center gap-3 border-t border-hairline pt-3">
            <span class="max-w-[44ch] text-xs text-pretty text-muted">{{ $unbuilt['compose'] }}</span>

            <span aria-disabled="true" class="{{ $inert }} ml-auto">{{ __('Save to their history') }}</span>
          </div>
        </x-panel>

        <section class="flex flex-col gap-3.5">
          <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-0.5">
            <h2 class="font-display text-section-title text-ink">{{ __('History') }}</h2>

            <p class="text-sm text-pretty text-muted">{{ $viewModel->historyNote() }}</p>
          </div>

          {{--
            The rail the entries hang from. The dot of each one sits on top of it
            and takes a plate in the page's own colour, so the line reads as
            passing behind rather than stopping at every entry.
          --}}
          <ol class="relative list-none pl-7.5 before:absolute before:top-1.5 before:bottom-1.5 before:left-2.75 before:w-0.5 before:rounded-[1px] before:bg-hairline before:content-['']">
            @foreach ($viewModel->history() as $entry)
              <li class="relative pb-6 last:pb-0">
                <span
                  class="absolute top-px -left-7.5 flex size-6 items-center justify-center rounded-full ring-4 ring-page {{ $entry['iconClass'] }}"
                  aria-hidden="true"
                >
                  <x-icon :name="$entry['icon']" :size="14" :stroke-width="1.6" />
                </span>

                <p class="text-xs text-muted">{{ $entry['when'] }}</p>

                <p class="mt-0.5 text-base font-semibold text-pretty text-ink">{{ $entry['title'] }}</p>

                <p class="mt-1.5 max-w-180 text-sm text-pretty text-body">{{ $entry['body'] }}</p>
              </li>
            @endforeach
          </ol>
        </section>
      </div>

      <div class="flex flex-col gap-5">
        <x-panel :title="__('Facts')" icon="info" icon-class="bg-terracotta-tint text-terracotta">
          <dl class="grid gap-0">
            @foreach ($viewModel->facts() as $fact)
              <div class="grid grid-cols-[150px_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline py-2.75 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
                <dt class="flex items-center gap-2 text-sm text-muted">
                  <x-icon :name="$fact['icon']" :size="15" :stroke-width="1.5" />
                  {{ $fact['label'] }}
                </dt>

                <dd @class(['text-base text-pretty', $fact['value'] ? 'text-ink' : 'text-placeholder'])>
                  {{ $fact['value'] ?? __('Not recorded') }}
                </dd>
              </div>
            @endforeach
          </dl>
        </x-panel>

        <x-panel :title="__('Relationships')" icon="relationship" icon-class="bg-rose-tint text-rose">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['relationships'] }}</p>
        </x-panel>

        <x-panel :title="__('Reminders')" icon="reminder" icon-class="bg-plum-tint text-plum">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['reminders'] }}</p>
        </x-panel>

        <x-panel :title="__('Things you said you would do')" icon="tasks" icon-class="bg-forest-tint text-forest">
          <p class="text-sm text-pretty text-muted">{{ $unbuilt['tasks'] }}</p>
        </x-panel>
      </div>
    </div>
  @else
    @include('app.vaults.contacts._page')
  @endif
</x-shell>
