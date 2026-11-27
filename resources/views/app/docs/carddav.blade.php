{{-- Connect a contacts or calendar app at /docs/carddav, open to anybody. --}}
{{-- @var \App\ViewModels\Docs\CarddavDocsViewModel $viewModel --}}
@use('App\ViewModels\Docs\CarddavDocsViewModel')
@use('Illuminate\Support\Str')
@php
  $clients = $viewModel->clients();
  $server = $viewModel->serverAddress();
  $inline = fn (string $text): string => Str::inlineMarkdown($text, ['html_input' => 'escape']);
  $prose = 'text-[14.5px] leading-[1.7] text-pretty text-body [&_strong]:font-semibold [&_strong]:text-ink';
  $heading = 'text-[22px] leading-[1.3] font-semibold tracking-[-0.02em] text-ink';
  $link = 'rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring';
  $action = 'flex h-7.5 items-center gap-1.5 rounded-md px-2 text-sm font-medium text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => CarddavDocsViewModel::TITLE, 'description' => CarddavDocsViewModel::LEAD])
    <title>{{ \Laravel\Head\Facades\Head::toArray()['title'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div class="flex min-h-screen flex-col" x-data="carddavDocs({{ Js::from([
      'instance' => $viewModel->instanceAddress(),
      'server' => $server,
      'tabs' => array_column($clients, 'key'),
      'sections' => array_column(CarddavDocsViewModel::SECTIONS, 'id'),
    ]) }})">
      <x-docs.header />

      <div class="border-b border-hairline bg-canvas print:hidden">
        <div class="mx-auto flex h-14 max-w-390 items-center gap-5 px-4 sm:px-7">
          <x-breadcrumb :items="[
            ['label' => __('Home page'), 'url' => route('home.index')],
            ['label' => __('Documentation'), 'url' => route('docs.index')],
            ['label' => 'CardDAV and CalDAV'],
          ]" />
        </div>
      </div>

      <div class="mx-auto flex w-full max-w-280 flex-1 items-start gap-16 px-4 pt-8 pb-24 sm:px-6">
        <main id="main-content" tabindex="-1" class="flex min-w-0 max-w-190 flex-1 flex-col gap-14 focus:outline-none">
          <section id="overview" class="flex scroll-mt-6 flex-col gap-5">
            <div class="flex flex-col gap-2.5">
              <x-caption tone="muted">CardDAV and CalDAV</x-caption>
              <h1 class="text-[34px] leading-[1.15] font-semibold tracking-[-0.03em] text-pretty text-ink">{{ CarddavDocsViewModel::TITLE }}</h1>
              <p class="text-[18px] leading-normal text-pretty text-ink-soft">{{ CarddavDocsViewModel::LEAD }}</p>
            </div>

            <x-docs.page-actions
              :markdown="$viewModel->markdown()"
              :markdown-url="route('docs.carddav.markdown.index')"
              transform="swap"
            />

            <p class="{{ $prose }}">{{ CarddavDocsViewModel::OVERVIEW }}</p>

            <div class="grid gap-3 sm:grid-cols-2">
              <a href="#connect" class="rounded-lg border border-hairline bg-canvas p-4 transition-colors hover:bg-hover focus-ring">
                <span class="block text-base font-semibold text-ink">Contacts · CardDAV</span>
                <span class="mt-1 block text-sm text-body">Add, edit and delete from either app.</span>
              </a>
              <a href="#calendars" class="rounded-lg border border-hairline bg-canvas p-4 transition-colors hover:bg-hover focus-ring">
                <span class="block text-base font-semibold text-ink">Calendars and tasks · CalDAV</span>
                <span class="mt-1 block text-sm text-body">View on your device. Make changes in Monica.</span>
              </a>
            </div>

            <x-docs.notice title="Deleting synced contacts">{{ CarddavDocsViewModel::DELETE_NOTICE }}</x-docs.notice>

            <details class="rounded-lg border border-hairline p-4 lg:hidden">
              <summary class="cursor-pointer text-sm font-semibold text-ink focus-ring">On this page</summary>
              <nav aria-label="On this page" class="mt-3 flex flex-col gap-2 text-sm">
                @foreach (CarddavDocsViewModel::SECTIONS as $section)
                  <a href="#{{ $section['id'] }}" class="{{ $link }}">{{ $section['label'] }}</a>
                @endforeach
              </nav>
            </details>
          </section>

          <section id="setup" aria-labelledby="setup-heading" class="flex scroll-mt-6 flex-col gap-4">
            <h2 id="setup-heading" class="{{ $heading }}">Before you connect</h2>
            <a href="{{ route('settings.security.index') }}#api-keys" class="{{ $link }} w-fit text-sm">Open Security settings <span aria-hidden="true">→</span></a>
            <x-docs.steps>
              @foreach (CarddavDocsViewModel::SETUP as $step)
                <x-docs.step><p class="{{ $prose }}">{!! $inline($step) !!}</p></x-docs.step>
              @endforeach
            </x-docs.steps>
            <x-docs.notice title="Access to your vaults">{{ CarddavDocsViewModel::ACCESS_NOTICE }}</x-docs.notice>
          </section>

          <section id="connect" aria-labelledby="connect-heading" class="flex scroll-mt-6 flex-col gap-5">
            <h2 id="connect-heading" class="{{ $heading }}">Connect your app</h2>
            <p class="{{ $prose }}">Every app uses the same connection details. Menu names vary by version. Contacts use CardDAV; calendars and reminder tasks use CalDAV.</p>

            <div class="max-w-105">
              <x-input id="instance-address" type="url" label="Your Monica address" :value="$viewModel->instanceAddress()" help="Use your instance’s address, without /dav. The server address below updates automatically." spellcheck="false" x-model="address" />
            </div>

            <dl class="overflow-hidden rounded-lg border border-hairline bg-canvas">
              <div class="flex flex-col gap-2 border-b border-hairline p-4">
                <dt id="server-label" class="text-sm font-semibold text-ink">Server address</dt>
                <dd class="flex items-start gap-3">
                  <code aria-labelledby="server-label" class="min-w-0 flex-1 font-mono text-sm break-all text-ink" x-text="serverAddress">{{ $server }}</code>
                  <button type="button" @click="copy('server', {{ Js::from($server) }})" class="{{ $action }} h-auto shrink-0 cursor-pointer">
                    <span aria-live="polite" x-text="label('server', 'Copy')">Copy</span><span class="sr-only">the server address</span>
                  </button>
                </dd>
              </div>
              <div class="grid gap-1 border-b border-hairline p-4 sm:grid-cols-[140px_1fr]">
                <dt class="text-sm font-semibold text-ink">Username</dt>
                <dd class="text-sm text-body">Your Monica email address</dd>
              </div>
              <div class="grid gap-1 p-4 sm:grid-cols-[140px_1fr]">
                <dt class="text-sm font-semibold text-ink">Password</dt>
                <dd class="text-sm text-body">Your API key. Your account password will not work.</dd>
              </div>
            </dl>

            <div role="tablist" aria-label="Contacts app" class="flex flex-wrap items-end gap-0.5 border-b border-hairline print:hidden" @keydown="move($event)">
              @foreach ($clients as $client)
                <button type="button" role="tab" id="client-tab-{{ $client['key'] }}" aria-controls="client-panel-{{ $client['key'] }}"
                  aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                  :aria-selected="tab === '{{ $client['key'] }}' ? 'true' : 'false'" :tabindex="tab === '{{ $client['key'] }}' ? '0' : '-1'"
                  @click="select('{{ $client['key'] }}')"
                  class="-mb-px flex h-9 flex-none cursor-pointer items-center border-b-2 border-transparent px-2.5 text-nav whitespace-nowrap text-muted transition-colors hover:text-ink focus-ring aria-selected:border-b-primary aria-selected:font-semibold aria-selected:text-ink"
                >{{ $client['label'] }}</button>
              @endforeach
            </div>

            @foreach ($clients as $client)
              <div id="client-panel-{{ $client['key'] }}" role="tabpanel" aria-labelledby="client-tab-{{ $client['key'] }}" tabindex="0"
                x-show="tab === '{{ $client['key'] }}'" class="flex flex-col gap-4 focus-ring print:block!">
                <h3 class="text-base font-semibold text-ink">{{ $client['label'] }}</h3>
                <x-docs.steps>
                  @foreach ($client['steps'] as $step)
                    <x-docs.step><p class="{{ $prose }}">{!! $inline($step) !!}</p></x-docs.step>
                  @endforeach
                </x-docs.steps>
                <div class="rounded-lg border border-hairline bg-canvas p-4">
                  <h4 class="mb-2 text-base font-semibold text-ink">Calendars and reminders</h4>
                  <p class="{{ $prose }}">{!! $inline($client['calendar']) !!}</p>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                  @foreach ($client['sources'] as $label => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="{{ $link }}">{{ $label }} <span aria-hidden="true">↗</span></a>
                  @endforeach
                </div>
              </div>
            @endforeach
          </section>

          <section id="check" aria-labelledby="check-heading" class="flex scroll-mt-6 flex-col gap-4">
            <h2 id="check-heading" class="{{ $heading }}">Check your connection</h2>
            <x-docs.steps>
              @foreach (CarddavDocsViewModel::CHECKS as $step)
                <x-docs.step><p class="{{ $prose }}">{{ $step }}</p></x-docs.step>
              @endforeach
            </x-docs.steps>
            <p class="{{ $prose }}">{{ CarddavDocsViewModel::TIMING }}</p>
          </section>

          <section id="sync" aria-labelledby="sync-heading" class="flex scroll-mt-6 flex-col gap-4">
            <h2 id="sync-heading" class="{{ $heading }}">What syncs</h2>
            <p class="{{ $prose }}">{{ CarddavDocsViewModel::CONTACTS }}</p>
            <div class="overflow-x-auto rounded-lg border border-hairline">
              <table class="w-full text-left text-sm">
                <caption class="sr-only">Information synced to your address book</caption>
                <thead class="bg-card text-ink"><tr><th scope="col" class="px-4 py-3 font-semibold">Information</th><th scope="col" class="px-4 py-3 font-semibold">What reaches your address book</th></tr></thead>
                <tbody>
                  @foreach (CarddavDocsViewModel::FIELDS as $field)
                    <tr class="border-t border-hairline align-top">
                      <th scope="row" class="w-1/3 px-4 py-3 font-semibold text-ink">{{ $field['label'] }}</th>
                      <td class="px-4 py-3 leading-relaxed text-body">{{ $field['text'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <x-docs.notice title="Sensitive fields and existing copies">{{ CarddavDocsViewModel::SENSITIVE }}</x-docs.notice>
          </section>

          @foreach (['changes' => ['Editing and deleting', CarddavDocsViewModel::CHANGES], 'calendars' => ['Calendars and reminders', CarddavDocsViewModel::CALENDARS], 'troubleshooting' => ['Troubleshooting', CarddavDocsViewModel::TROUBLESHOOTING]] as $id => [$title, $entries])
            <section id="{{ $id }}" aria-labelledby="{{ $id }}-heading" class="flex scroll-mt-6 flex-col gap-4">
              <h2 id="{{ $id }}-heading" class="{{ $heading }}">{{ $title }}</h2>
              @if ($id === 'changes')
                <x-docs.notice title="Moving between vaults does not transfer the history">{{ CarddavDocsViewModel::MOVE_NOTICE }}</x-docs.notice>
              @endif
              <dl>
                @foreach ($entries as $entry)
                  <div class="grid gap-x-5 gap-y-1 border-t border-hairline py-3.5 sm:grid-cols-[minmax(0,190px)_minmax(0,1fr)]">
                    <dt class="text-[14px] leading-[1.6] font-semibold text-ink">{{ $entry['title'] }}</dt>
                    <dd class="{{ $prose }}">{{ $entry['text'] }}</dd>
                  </div>
                @endforeach
              </dl>
            </section>
          @endforeach

          <section id="disconnect" aria-labelledby="disconnect-heading" class="flex scroll-mt-6 flex-col gap-4">
            <h2 id="disconnect-heading" class="{{ $heading }}">Disconnect a device</h2>
            <x-docs.steps>
              @foreach (CarddavDocsViewModel::DISCONNECT as $step)
                <x-docs.step><p class="{{ $prose }}">{!! $inline($step) !!}</p></x-docs.step>
              @endforeach
            </x-docs.steps>
            <a href="{{ route('settings.security.index') }}#api-keys" class="{{ $link }} w-fit text-sm">Manage your API keys <span aria-hidden="true">→</span></a>
          </section>
        </main>

        <aside aria-labelledby="on-this-page" class="sticky top-8 hidden w-50 flex-none flex-col gap-2.5 lg:flex print:hidden">
          <x-docs.page-meta class="mb-4" :updated="$viewModel->lastUpdated()" :edit-url="$viewModel->editUrl()" :issue-url="$viewModel->issueUrl()" />
          <x-caption id="on-this-page" tone="muted">On this page</x-caption>
          <nav aria-labelledby="on-this-page" class="flex flex-col gap-0.5 border-l border-hairline">
            @foreach (CarddavDocsViewModel::SECTIONS as $section)
              <a href="#{{ $section['id'] }}" :aria-current="current === '{{ $section['id'] }}' ? 'location' : null"
                class="-ml-px border-l border-transparent py-1.25 pl-3 text-sm leading-[1.4] text-muted transition-colors hover:text-ink focus-ring aria-[current]:border-ink aria-[current]:font-semibold aria-[current]:text-ink"
              >{{ $section['label'] }}</a>
            @endforeach
          </nav>
        </aside>
      </div>
    </div>
  </body>
</html>
