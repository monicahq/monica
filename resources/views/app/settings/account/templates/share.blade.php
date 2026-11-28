{{-- The two ways a template leaves an account, side by side. --}}
{{-- @var \App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel $viewModel --}}
@php
  $template = $viewModel->template();
@endphp
<x-settings.page
  :title="__('Share :name', ['name' => $template->name])"
  :user="$viewModel->user()"
  current="templates"
  :crumbs="[
      ['label' => __('Templates'), 'url' => route('settings.templates.index')],
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => __('Share')],
    ]"
  wide
>
  <header class="flex max-w-prose flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Share :name', ['name' => $template->name]) }}</h1>

    <p class="text-base text-pretty text-body">
      @if ($viewModel->marketplaceIsOn())
        {{ __('Two ways to share, for two different reasons. A file is for you and for the people you give it to. A listing is for everybody else. Either way only the structure leaves this vault, and the :records using this template stay here.', ['records' => $viewModel->recordCountLabel()]) }}
      @else
        {{ __('A file is for you and for the people you give it to. Only the structure leaves this vault, and the :records using this template stay here.', ['records' => $viewModel->recordCountLabel()]) }}
      @endif
    </p>
  </header>

  <div class="grid items-start gap-6 lg:grid-cols-2">
    <x-panel :title="__('Export a file')" :subtitle="__('A JSON file on your computer. Nothing is sent anywhere.')" icon="file">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('The whole template written out as text: every section, every field, every value list, in the order you arranged them. Import it into another vault or another instance and you get this template back, empty.') }}
        </p>

        <ul class="flex flex-col gap-2.5">
          @foreach ([
            __('Move a template between your own vaults, or onto a new instance.'),
            __('Give it to one person, on your terms, without publishing anything.'),
            __('Keep it as a backup of a structure that took you a while to get right.'),
          ] as $reason)
            <li class="flex items-start gap-2.5">
              <span class="mt-px flex size-5 shrink-0 items-center justify-center rounded-sm bg-forest-tint text-forest-deep">
                <x-icon name="check" :size="13" :stroke-width="2" />
              </span>

              <span class="text-sm text-pretty text-ink-soft">{{ $reason }}</span>
            </li>
          @endforeach
        </ul>

        <div class="flex items-center gap-2.5 rounded-md border border-hairline bg-card px-3.5 py-3">
          <x-icon name="file" :size="16" :stroke-width="1.6" class="text-muted" />

          <code class="min-w-0 truncate font-mono text-xs text-ink">{{ $viewModel->fileName() }}</code>

          <span class="ms-auto shrink-0 text-xs text-muted">{{ $viewModel->sizeLabel() }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-button :href="$viewModel->downloadUrl()" download data-no-instant>{{ __('Export the template') }}</x-button>

          <x-button.ghost :href="$viewModel->exportUrl()" data-turbo="true">{{ __('See what is inside') }}</x-button.ghost>
        </div>
      </div>
    </x-panel>

    @if ($viewModel->marketplaceIsOn())
      <x-panel :title="__('Publish to the marketplace')" :subtitle="__('A listing anybody can install.')" icon="gift">
        <div class="flex flex-col gap-4">
          <p class="text-base text-pretty text-body">
            {{ __('The marketplace is where people share the templates they built for themselves: a dog profile, a template for a therapist’s clients, one for a band’s contacts. Somebody has usually already built the thing you need.') }}
          </p>

          <ul class="flex flex-col gap-2.5">
            @foreach ([
              __('People find it by searching for the thing they are trying to track.'),
              __('Installing makes a private copy in their vault, which you never see again.'),
              __('You can post a new version later, and nobody moves onto it unless they choose to.'),
            ] as $reason)
              <li class="flex items-start gap-2.5">
                <span class="mt-px flex size-5 shrink-0 items-center justify-center rounded-sm bg-forest-tint text-forest-deep">
                  <x-icon name="check" :size="13" :stroke-width="2" />
                </span>

                <span class="text-sm text-pretty text-ink-soft">{{ $reason }}</span>
              </li>
            @endforeach
          </ul>

          <x-notice tone="warning" :title="__('Writing a listing is not open yet')">
            {{ __('You can read the catalogue today. Publishing your own work into it is the next thing being built. Exporting a file works either way.') }}
          </x-notice>

          <div>
            <x-button.secondary :href="route('marketplace.index')">{{ __('Browse the marketplace') }}</x-button.secondary>
          </div>
        </div>
      </x-panel>
    @endif
  </div>

  <x-notice tone="quiet" icon="lock" :title="__('What never leaves, either way')">
    {{ __('No contacts, no notes, no reminders, no files, no photos. A field called Nickname is exported; the nicknames you wrote in it are not. You can read the whole file before it goes anywhere.') }}
  </x-notice>
</x-settings.page>
