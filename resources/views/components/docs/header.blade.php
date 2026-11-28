{{-- The top bar of every documentation page, drawn as the public marketing site draws its own. --}}
@php
  use App\Helpers\MarketingSite;

  $navLink = 'flex items-center gap-1 rounded-md px-2.5 py-2 text-base font-medium text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink aria-[current]:font-semibold aria-[current]:text-ink focus-ring';
  $summary = "{$navLink} cursor-pointer list-none [&::-webkit-details-marker]:hidden";
  $panel = 'flex max-h-[calc(100dvh-140px)] origin-top-left rtl:origin-top-right flex-wrap overflow-y-auto rounded-lg border border-hairline bg-canvas shadow-pop transition-[opacity,scale] duration-300 ease-pop starting:scale-90 starting:opacity-0 motion-reduce:transition-none';
  $groupLink = 'flex flex-col gap-2.5 rounded-lg p-3 text-ink no-underline hover:bg-hover focus-ring';
  $caption = 'text-2xs font-semibold tracking-[0.08em] text-muted uppercase';
  $chip = 'flex items-center gap-1.5 rounded-md border border-hairline bg-canvas py-1.25 pe-2 ps-2.5 text-base font-medium text-ink no-underline shadow-panel hover:border-hairline-strong hover:bg-hover focus-ring';
  $card = 'rounded-lg border border-hairline bg-canvas p-5 text-ink no-underline shadow-panel hover:border-hairline-strong hover:bg-hover focus-ring';
  $repository = 'https://github.com/monicahq/monica';
  $starFigure = '25k+';

  $features = [
    [
      'icon' => 'people',
      'tone' => ['tile' => 'bg-identity-7/12 text-identity-7', 'icon' => 'text-identity-7'],
      'title' => __('People'),
      'body' => __('Everyone you know, and what you know about them.'),
      'href' => MarketingSite::url('people'),
      'items' => [
        ['icon' => 'profile', 'title' => __('Contact details'), 'body' => __('Phones, addresses, birthdays, pets.'), 'href' => MarketingSite::url('details')],
        ['icon' => 'relationship', 'title' => __('Relationships'), 'body' => __('Family trees, and who knows whom.'), 'href' => MarketingSite::url('relationships')],
        ['icon' => 'reminder', 'title' => __('Timeline & reminders'), 'body' => __('Log it once, hear about it on time.'), 'href' => MarketingSite::url('timelineReminders')],
      ],
    ],
    [
      'icon' => 'tag',
      'tone' => ['tile' => 'bg-identity-1/12 text-identity-1', 'icon' => 'text-identity-1'],
      'title' => __('Possessions'),
      'body' => __('What you own, where it is, and who has it.'),
      'href' => MarketingSite::url('possessions'),
      'items' => [
        ['icon' => 'layers', 'title' => __('Inventory & tags'), 'body' => __('Books, records, tools, the lot.'), 'href' => MarketingSite::url('inventory')],
        ['icon' => 'place', 'title' => __('Locations'), 'body' => __('Which room, which shelf, which box.'), 'href' => MarketingSite::url('locations')],
        ['icon' => 'calendar-repeat', 'title' => __('Maintenance & loans'), 'body' => __('Service dates, and who borrowed what.'), 'href' => MarketingSite::url('maintenanceLoans')],
      ],
    ],
    [
      'icon' => 'journal',
      'tone' => ['tile' => 'bg-identity-2/12 text-identity-2', 'icon' => 'text-identity-2'],
      'title' => __('Knowledge'),
      'body' => __('What you have learned, written down where you will find it.'),
      'href' => MarketingSite::url('knowledge'),
      'items' => [
        ['icon' => 'note', 'title' => __('Notes'), 'body' => __('Write it down, link it to anyone.'), 'href' => MarketingSite::url('notes')],
        ['icon' => 'tasks', 'title' => __('Lists'), 'body' => __('Gift ideas, packing, things to do.'), 'href' => MarketingSite::url('lists')],
        ['icon' => 'file', 'title' => __('Folders & organisation'), 'body' => __('Group things the way you think.'), 'href' => MarketingSite::url('folders')],
      ],
    ],
  ];

  $spaces = [
    ['icon' => 'divider', 'title' => __('Templates'), 'href' => MarketingSite::url('templates')],
    ['icon' => 'vault', 'title' => __('Vaults'), 'href' => MarketingSite::url('vaults')],
    ['icon' => 'link', 'title' => __('Sharing'), 'href' => MarketingSite::url('sharing')],
    ['icon' => 'text', 'title' => __('Languages'), 'href' => MarketingSite::url('languages')],
    ['icon' => 'lock', 'title' => __('Privacy'), 'href' => MarketingSite::url('privacyFeatures')],
  ];

  $docs = [
    [
      'icon' => 'journal',
      'tone' => ['tile' => 'bg-identity-5/12 text-identity-5', 'icon' => 'text-identity-5'],
      'title' => __('Handbook'),
      'body' => __('How the project is run, and why it is built the way it is.'),
      'href' => MarketingSite::url('handbook'),
      'items' => [
        ['icon' => 'heart', 'title' => __('Principles'), 'href' => MarketingSite::handbookArticle('principles')],
        ['icon' => 'coin', 'title' => __('How Monica makes money'), 'href' => MarketingSite::handbookArticle('money')],
        ['icon' => 'lock', 'title' => __('Privacy and data'), 'href' => MarketingSite::handbookArticle('your-data')],
      ],
    ],
    [
      'icon' => 'note',
      'tone' => ['tile' => 'bg-identity-6/12 text-identity-6', 'icon' => 'text-identity-6'],
      'title' => __('Documentation'),
      'body' => __('Everything you need to set up Monica and get the most out of it.'),
      'href' => route('docs.index'),
      'items' => [
        ['icon' => 'check-circle', 'title' => __('Getting started'), 'href' => route('docs.portal.index', ['locale' => 'en'])],
        ['icon' => 'settings', 'title' => __('Self-hosting'), 'href' => $repository],
        ['icon' => 'key', 'title' => __('API reference'), 'href' => route('docs.api.index')],
      ],
    ],
    [
      'icon' => 'people',
      'tone' => ['tile' => 'bg-identity-3/12 text-identity-3', 'icon' => 'text-identity-3'],
      'title' => __('Contribute'),
      'body' => __('Monica is open source. Help with code, words or a bug report.'),
      'href' => MarketingSite::url('contribute'),
      'items' => [
        ['icon' => 'code', 'title' => __('Source code'), 'href' => $repository],
        ['icon' => 'globe', 'title' => __('Translations'), 'href' => MarketingSite::url('contribute')],
        ['icon' => 'info', 'title' => __('Report a bug'), 'href' => "{$repository}/issues"],
      ],
    ],
  ];

  $quickLinks = [
    ['icon' => 'layers', 'title' => __('Changelog'), 'href' => 'https://my.monicahq.com/changelog'],
    ['icon' => 'tasks', 'title' => __('Roadmap'), 'href' => MarketingSite::handbookArticle('roadmap')],
    ['icon' => 'mail', 'title' => __('Support'), 'href' => MarketingSite::url('support')],
  ];
@endphp

<header class="relative z-40 border-b border-hairline bg-canvas print:hidden">
  <div class="relative mx-auto flex min-h-15 w-full max-w-390 flex-wrap items-center gap-x-8 gap-y-2 px-4 max-lg:py-3 sm:px-7">
    <a href="{{ MarketingSite::url('home') }}" class="flex items-center gap-2 rounded-md text-ink no-underline focus-ring">
      <x-logo :size="22" />
      <span class="text-lede-sm font-semibold tracking-[-0.02em]">{{ config('app.name') }}</span>
    </a>

    <nav
      aria-label="{{ __('Main') }}"
      class="flex min-w-0 flex-1 flex-wrap items-center gap-1 max-lg:order-3 max-lg:-mx-2.5 max-lg:basis-full"
    >
      <details x-data="headerMenu" data-menu class="group/menu">
        <summary class="{{ $summary }} group-open/menu:bg-hover group-open/menu:text-ink">
          {{ __('Features') }}
          <x-icon name="chevron-down" :size="14" class="transition-transform duration-(--duration-fast) ease-out group-open/menu:rotate-180" />
        </summary>

        <div class="absolute inset-x-4 top-full z-50 pt-2 sm:inset-x-7">
          <div class="{{ $panel }}">
            <div class="flex min-w-0 flex-[3_1_700px] flex-col">
              <div class="grid gap-2 px-5 pt-6 pb-5 sm:grid-cols-3 sm:gap-y-1">
                @foreach ($features as $group)
                  <section class="flex min-w-0 flex-col gap-1 sm:row-span-5 sm:grid sm:grid-rows-subgrid">
                    <a href="{{ $group['href'] }}" @if ($group['href'] === url()->current()) aria-current="page" @endif class="{{ $groupLink }}">
                      <span class="flex items-center gap-2.5">
                        <span class="grid size-8 flex-none place-items-center rounded-md {{ $group['tone']['tile'] }}">
                          <x-icon :name="$group['icon']" :size="18" />
                        </span>
                        <span class="text-lede-sm font-semibold tracking-[-0.01em]">{{ $group['title'] }}</span>
                        <span class="ms-auto text-muted">
                          <x-icon name="arrow-right" :size="16" />
                        </span>
                      </span>
                      <span class="text-base leading-snug text-pretty text-body">{{ $group['body'] }}</span>
                    </a>
                    <div class="mx-3 my-1 h-px bg-hairline-soft"></div>
                    @foreach ($group['items'] as $item)
                      <a href="{{ $item['href'] }}" class="flex gap-2.5 rounded-md px-3 py-2 text-ink no-underline hover:bg-hover focus-ring">
                        <span class="flex-none pt-px {{ $group['tone']['icon'] }}">
                          <x-icon :name="$item['icon']" :size="16" />
                        </span>
                        <span class="flex flex-col gap-0.5">
                          <span class="text-base font-medium">{{ $item['title'] }}</span>
                          <span class="text-xs text-muted">{{ $item['body'] }}</span>
                        </span>
                      </a>
                    @endforeach
                  </section>
                @endforeach
              </div>

              <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-hairline bg-card px-8 py-3.5">
                <span class="me-3 {{ $caption }}">{{ __('In every space') }}</span>
                @foreach ($spaces as $space)
                  <a href="{{ $space['href'] }}" class="{{ $chip }}">
                    <x-icon :name="$space['icon']" :size="15" />
                    {{ $space['title'] }}
                    <span class="ms-0.5 text-muted">
                      <x-icon name="chevron-right" :size="14" />
                    </span>
                  </a>
                @endforeach
              </div>
            </div>

            <aside class="flex min-w-0 flex-[1_1_300px] flex-col border-hairline bg-card p-6 max-lg:border-t lg:border-s">
              <a href="{{ MarketingSite::url('mcpServer') }}" class="{{ $card }} flex flex-1 flex-wrap content-between gap-4">
                <span class="flex min-w-0 flex-[1_1_240px] flex-col gap-1.5">
                  <span class="{{ $caption }}">{{ __('New') }}</span>
                  <span class="text-lede-sm leading-snug font-semibold tracking-[-0.015em] text-balance">{{ __('Use Monica with your AI assistant') }}</span>
                  <span class="text-base text-pretty text-body">{{ __('Ask about the people and things you keep in Monica. You choose which vaults it can read.') }}</span>
                </span>
                <span aria-hidden="true" class="flex min-w-0 flex-[1_1_240px] flex-col justify-center gap-2 rounded-md border border-hairline-soft bg-card p-3">
                  <span class="max-w-[85%] self-end rounded-lg bg-primary px-2.5 py-1.75 text-xs leading-snug text-on-primary">{{ __('When is Lucie’s birthday?') }}</span>
                  <span class="max-w-[92%] self-start rounded-lg border border-hairline bg-canvas px-2.5 py-2 text-xs leading-snug text-ink-soft">{{ __('14 March, in three weeks. In January she mentioned wanting a new rain jacket.') }}</span>
                </span>
                <span class="mt-auto flex basis-full items-center gap-1.5 text-base font-medium">
                  {{ __('Connect your assistant') }}
                  <x-icon name="arrow-right" :size="15" />
                </span>
              </a>
            </aside>
          </div>
        </div>
      </details>

      <a href="{{ MarketingSite::url('pricing') }}" class="{{ $navLink }}">{{ __('Pricing') }}</a>
      <a href="{{ MarketingSite::url('blog') }}" class="{{ $navLink }}">{{ __('Blog') }}</a>

      <details x-data="headerMenu" data-menu class="group/docs">
        <summary aria-current="true" class="{{ $summary }} group-open/docs:bg-hover group-open/docs:text-ink">
          {{ __('Docs') }}
          <x-icon name="chevron-down" :size="14" class="transition-transform duration-(--duration-fast) ease-out group-open/docs:rotate-180" />
        </summary>

        <div class="absolute inset-x-4 top-full z-50 pt-2 sm:inset-x-7">
          <div class="{{ $panel }}">
            <div class="flex min-w-0 flex-[3_1_700px] flex-col">
              <div class="grid gap-2 px-5 pt-6 pb-5 sm:grid-cols-3 sm:gap-y-1">
                @foreach ($docs as $group)
                  <section class="flex min-w-0 flex-col gap-1 sm:row-span-6 sm:grid sm:grid-rows-subgrid">
                    <a href="{{ $group['href'] }}" @if ($group['href'] === url()->current()) aria-current="page" @endif class="{{ $groupLink }}">
                      <span class="flex items-center gap-2.5">
                        <span class="grid size-8 flex-none place-items-center rounded-md {{ $group['tone']['tile'] }}">
                          <x-icon :name="$group['icon']" :size="18" />
                        </span>
                        <span class="text-lede-sm font-semibold tracking-[-0.01em]">{{ $group['title'] }}</span>
                        <span class="ms-auto text-muted">
                          <x-icon name="arrow-right" :size="16" />
                        </span>
                      </span>
                      <span class="text-base leading-snug text-pretty text-body">{{ $group['body'] }}</span>
                    </a>
                    <div class="mx-3 my-1 h-px bg-hairline-soft"></div>
                    <span class="px-3 pt-1 pb-0.5 {{ $caption }}">{{ __('Inside') }}</span>
                    @foreach ($group['items'] as $item)
                      <a href="{{ $item['href'] }}" @if ($item['href'] === url()->current()) aria-current="page" @endif class="flex items-center gap-2.5 rounded-md px-3 py-2 text-base font-medium text-ink no-underline hover:bg-hover focus-ring">
                        <span class="flex-none {{ $group['tone']['icon'] }}">
                          <x-icon :name="$item['icon']" :size="16" />
                        </span>
                        {{ $item['title'] }}
                      </a>
                    @endforeach
                  </section>
                @endforeach
              </div>

              <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-hairline bg-card px-8 py-3.5">
                <span class="me-3 {{ $caption }}">{{ __('Quick links') }}</span>
                @foreach ($quickLinks as $quickLink)
                  <a href="{{ $quickLink['href'] }}" class="{{ $chip }}">
                    <x-icon :name="$quickLink['icon']" :size="15" />
                    {{ $quickLink['title'] }}
                    <span class="ms-0.5 text-muted">
                      <x-icon name="chevron-right" :size="14" />
                    </span>
                  </a>
                @endforeach
              </div>
            </div>

            <aside class="flex min-w-0 flex-[1_1_300px] flex-col border-hairline bg-card p-6 max-lg:border-t lg:border-s">
              <a href="{{ $repository }}" class="{{ $card }} flex flex-1 flex-col gap-1.5">
                <span class="{{ $caption }}">{{ __('Start here') }}</span>
                <span class="text-lede-sm leading-snug font-semibold tracking-[-0.015em] text-balance">{{ __('Install Monica on your own server') }}</span>
                <span class="text-base text-pretty text-body">{{ __('Your data stays on a machine you control. One Docker command, about ten minutes.') }}</span>
                <code class="mt-2 truncate rounded-md border border-hairline-soft bg-card px-3 py-2.5 font-mono text-xs text-ink-soft">docker run monica</code>
                <span class="mt-auto flex items-center gap-1.5 pt-4 text-base font-medium">
                  {{ __('Read the install guide') }}
                  <x-icon name="arrow-right" :size="15" />
                </span>
              </a>
            </aside>
          </div>
        </div>
      </details>

      <a href="{{ MarketingSite::url('sales') }}" class="{{ $navLink }}">{{ __('Contact sales') }}</a>
    </nav>

    <div class="flex items-center gap-2 max-lg:ms-auto">
      <a
        href="{{ $repository }}"
        aria-label="{{ __(':figure stars, Monica on GitHub', ['figure' => $starFigure]) }}"
        class="hidden h-9 items-center gap-2 rounded-md px-2.5 text-base font-medium text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring sm:flex"
      >
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false" class="block flex-none"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"></path></svg>
        <span>{{ $starFigure }}</span>
      </a>

      <button
        x-data
        type="button"
        @click="$store.theme.toggle()"
        class="inline-flex size-9 flex-none cursor-pointer items-center justify-center rounded-md text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
      >
        <span class="inline-flex dark:hidden">
          <x-icon name="moon" :size="16" />
          <span class="sr-only">{{ __('Switch to dark mode') }}</span>
        </span>
        <span class="hidden dark:inline-flex">
          <x-icon name="sun" :size="16" />
          <span class="sr-only">{{ __('Switch to light mode') }}</span>
        </span>
      </button>

      @auth
        <x-button :href="route('home.index')">{{ __('Dashboard') }}</x-button>
      @else
        <a
          href="{{ route('auth.login.new') }}"
          class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-hairline bg-canvas px-3.75 text-base font-medium whitespace-nowrap text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
        >{{ __('Log in') }}</a>

        @if (config('monica.registration_enabled'))
          <x-button :href="route('auth.register.new')">{{ __('Sign up') }}</x-button>
        @endif
      @endauth
    </div>
  </div>
</header>
