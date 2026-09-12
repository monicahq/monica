{{-- What merging one contact into another would do, decided difference by difference before it runs. --}}
{{-- @var \App\ViewModels\Vaults\ContactMergeViewModel $viewModel --}}
@php
  $preview = $viewModel->preview();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => route('contacts.index', $viewModel->vault())],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->backUrl()],
        ['label' => __('Merge'), 'url' => $viewModel->pickerUrl()],
        ['label' => $preview['other']['name']],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  <x-error :messages="$errors->get('fingerprint')" />

  <x-panel :title="__('The two records')" icon="contact" :flush="true">
    <ul class="grid gap-0">
      @foreach ($viewModel->records() as $record)
        <li class="flex items-center gap-3 border-b border-hairline px-4.5 py-3 last:border-b-0">
          <x-contact-avatar :name="$record['name']" :url="$record['avatarUrl']" :size="38" />

          <x-link :href="$record['url']" target="_blank" rel="noopener" class="text-sm">{{ $record['name'] }}</x-link>
        </li>
      @endforeach
    </ul>
  </x-panel>

  <x-form method="post" :action="$viewModel->confirmUrl()">
    <input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}" />

    @if ($preview['conflicts'] === [] && $preview['collisions'] === [])
      <x-panel :title="__('Nothing to decide')" icon="check">
        <p class="text-sm text-ink-soft">
          {{ __('The two records do not disagree anywhere, so everything simply comes together.') }}
        </p>
      </x-panel>
    @endif

    @foreach ($preview['conflicts'] as $index => $conflict)
      <x-panel :title="$conflict['label']" icon="file">
        <fieldset class="grid gap-2">
          <legend class="mb-1 text-sm text-ink-soft">
            {{ __('These two records disagree here. Which one is right?') }}
          </legend>

          <input type="hidden" name="resolutions[{{ $index }}][field_key]" value="{{ $conflict['field_key'] }}" />
          <input type="hidden" name="resolutions[{{ $index }}][row]" value="" />

          <label class="flex items-start gap-2 rounded-lg border border-hairline px-3 py-2">
            <input type="radio" name="resolutions[{{ $index }}][keep]" value="kept" checked class="mt-0.5" />
            <span class="grid gap-0.5">
              <span class="text-sm font-semibold text-ink">{{ $preview['kept']['name'] }}</span>
              <span class="text-sm text-ink-soft">{{ $conflict['kept_value'] }}</span>
            </span>
          </label>

          <label class="flex items-start gap-2 rounded-lg border border-hairline px-3 py-2">
            <input type="radio" name="resolutions[{{ $index }}][keep]" value="other" class="mt-0.5" />
            <span class="grid gap-0.5">
              <span class="text-sm font-semibold text-ink">{{ $preview['other']['name'] }}</span>
              <span class="text-sm text-ink-soft">{{ $conflict['other_value'] }}</span>
            </span>
          </label>
        </fieldset>
      </x-panel>
    @endforeach

    @foreach ($preview['collisions'] as $offset => $collision)
      @php
        $index = count($preview['conflicts']) + $offset;
      @endphp

      <x-panel :title="__('The same entry twice')" icon="file">
        <fieldset class="grid gap-2">
          <legend class="mb-1 text-sm text-ink-soft">
            {{ __('Both records hold this identical entry under :field.', ['field' => $collision['field_key']]) }}
          </legend>

          <input type="hidden" name="resolutions[{{ $index }}][field_key]" value="{{ $collision['field_key'] }}" />
          <input type="hidden" name="resolutions[{{ $index }}][row]" value="{{ $collision['row'] }}" />

          <label class="flex items-center gap-2 rounded-lg border border-hairline px-3 py-2">
            <input type="radio" name="resolutions[{{ $index }}][keep]" value="kept" checked />
            <span class="text-sm text-ink">{{ __('Keep it once') }}</span>
          </label>

          <label class="flex items-center gap-2 rounded-lg border border-hairline px-3 py-2">
            <input type="radio" name="resolutions[{{ $index }}][keep]" value="both" />
            <span class="text-sm text-ink">{{ __('Keep both entries') }}</span>
          </label>
        </fieldset>
      </x-panel>
    @endforeach

    @if ($preview['moves'] !== [])
      <x-panel :title="__('What comes over')" icon="file" :flush="true">
        <ul class="grid gap-0">
          @foreach ($preview['moves'] as $move)
            <li class="border-b border-hairline px-4.5 py-3 text-sm text-ink last:border-b-0">
              {{ $viewModel->movedLine($move) }}
            </li>
          @endforeach
        </ul>
      </x-panel>
    @endif

    @if ($preview['set_aside'] !== [])
      <x-notice tone="warning" :title="__('Set aside rather than shown')">
        <p class="text-sm">
          {{ __('These answers have no matching question on the surviving record, so they are kept out of the way instead of being thrown away.') }}
        </p>

        <ul class="mt-2 grid gap-1">
          @foreach ($preview['set_aside'] as $aside)
            <li class="text-sm">{{ $aside['label'] }}</li>
          @endforeach
        </ul>
      </x-notice>
    @endif

    <x-notice tone="danger" :title="__('This absorbs a contact')">
      <p class="text-sm">{{ $viewModel->warning() }}</p>
    </x-notice>

    <div class="mt-4 flex items-center gap-2">
      <x-button type="submit">
        {{ __('Merge :other into :name', ['other' => $preview['other']['name'], 'name' => $preview['kept']['name']]) }}
      </x-button>

      <x-button.secondary :href="$viewModel->pickerUrl()" data-turbo="true">
        {{ __('Choose somebody else') }}
      </x-button.secondary>
    </div>
  </x-form>
</x-shell>
