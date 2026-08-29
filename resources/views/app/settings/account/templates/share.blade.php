{{--
  The two ways a template leaves an account, side by side.

  They are drawn as two equal cards rather than one button and a link, because
  they are two different decisions and not a choice with a default. A file
  involves nobody. A listing involves everybody. Somebody who has only ever seen
  one of them would never think to look for the other.

  The panel at the foot is the one thing on the screen that is not about the
  choice. It is the promise the whole feature rests on, and it is repeated on
  both paths because it applies to both.

  @var \App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel $viewModel
--}}
@php
  $template = $viewModel->template();
@endphp
<x-shell :title="__('Share :name', ['name' => $template->name])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[
      ['label' => __('Templates'), 'url' => route('settings.templates.index')],
      ['label' => $template->name, 'url' => $viewModel->builderUrl()],
      ['label' => __('Share')],
    ]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="templates" />
  </x-slot:sidebar>

  <header class="flex max-w-[66ch] flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Share :name', ['name' => $template->name]) }}</h1>

    <p class="text-base text-pretty text-body">
      {{ __('Two ways out, for two different reasons. A file is for you, and for the people you hand it to. A listing is for everybody else. Either way only the structure leaves this vault, and the :records using this template stay here.', ['records' => $viewModel->recordCountLabel()]) }}
    </p>
  </header>

  <div class="grid items-start gap-6 lg:grid-cols-2">
    <x-panel :title="__('Export a file')" :subtitle="__('A JSON file on your computer. Nothing is sent anywhere.')" icon="file">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('The whole template written out as text: every section, every field, every list of choices, in the order you arranged them. Import it into another vault or another instance and you get this template back, empty.') }}
        </p>

        <ul class="flex flex-col gap-2.5">
          @foreach ([
            __('Move a template between your own vaults, or onto a new instance.'),
            __('Hand it to one person, on your terms, without publishing anything.'),
            __('Keep it as a backup of a shape that took you a while to get right.'),
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

          <code class="min-w-0 truncate font-mono text-[12.5px] text-ink">{{ $viewModel->fileName() }}</code>

          <span class="ml-auto shrink-0 text-xs text-muted">{{ $viewModel->sizeLabel() }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <x-button :href="$viewModel->downloadUrl()">{{ __('Export the template') }}</x-button>

          <x-button.ghost :href="$viewModel->exportUrl()" data-turbo="true">{{ __('See what is inside') }}</x-button.ghost>
        </div>
      </div>
    </x-panel>

    <x-panel :title="__('Publish to the marketplace')" :subtitle="__('A listing anybody can install.')" icon="gift">
      <div class="flex flex-col gap-4">
        <p class="text-base text-pretty text-body">
          {{ __('The marketplace is where people share the shapes they worked out for themselves: a dog profile, a template for a therapist’s clients, one for a band’s contacts. Somebody has usually already thought about the thing you are about to build.') }}
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
          {{ __('The catalogue is readable today, and publishing your own work into it is the next thing being built. Exporting a file works either way.') }}
        </x-notice>

        <div>
          <x-button.secondary :href="route('marketplace.index')">{{ __('Browse the marketplace') }}</x-button.secondary>
        </div>
      </div>
    </x-panel>
  </div>

  <x-notice tone="quiet" icon="lock" :title="__('What never leaves, either way')">
    {{ __('No contacts, no notes, no reminders, no files, no photos. A field called Nickname travels; the nicknames you wrote in it do not. You can read the whole file before it goes anywhere.') }}
  </x-notice>
</x-shell>
