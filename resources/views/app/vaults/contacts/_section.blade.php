{{-- One block of the record, in one of its two states. --}}
{{--
  @var array<string, mixed> $section
  @var \App\ViewModels\Vaults\ContactViewModel $viewModel
--}}
<x-panel
  :id="$section['regionId']"
  x-merge="replace"
  :title="$section['name']"
  :icon="$section['icon']"
  :tint="$section['tint']"
  flush
  class="[&[aria-busy]]:opacity-60"
>
  <x-slot:action>
    @if ($section['isEditing'])
      <span class="text-xs tracking-caption text-muted uppercase">{{ __('Editing') }}</span>
    @elseif ($section['isEditable'])
      <x-button.secondary
        size="small"
        :href="$section['editUrl']"
        :x-target="$section['regionId']"
      >{{ __('Edit') }}</x-button.secondary>
    @endif
  </x-slot:action>

  @if ($section['isEditing'])
    <x-form
      method="put"
      :action="$section['saveUrl']"
      :upload="$section['holdsAFile']"
      :x-target="$section['regionId'].' contact-facts'"
      class="flex flex-col gap-5 bg-page p-4.5"
    >
      @if ($errors->has('fields'))
        <x-notice tone="danger">{{ $errors->first('fields') }}</x-notice>
      @endif

      @forelse ($section['fields'] as $field)
        <x-field-input
          :field="$field['field']"
          :value="$field['rawValue']"
          :formatted="$field['value']"
          :rows="$field['rows']"
          :reminder="$field['reminder']"
          :messages="$errors"
          :error="$errors->get('fields.'.$field['id'])"
        />
      @empty
        <p class="text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse

      <div class="flex flex-wrap items-center gap-3 border-t border-hairline pt-3.5">
        <p class="max-w-[34ch] text-xs text-pretty text-muted">{{ $section['saveLine'] }}</p>

        <div class="ml-auto flex items-center gap-2">
          <x-button.secondary
            size="small"
            :href="$viewModel->pageUrl()"
            :x-target="$section['regionId']"
          >{{ __('Cancel') }}</x-button.secondary>

          <x-button type="submit" size="small">{{ __('Save this section') }}</x-button>
        </div>
      </div>
    </x-form>
  @else
    <dl class="grid gap-0">
      @forelse ($section['fields'] as $field)
        <div class="grid grid-cols-[140px_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
          <dt class="text-sm text-muted">{{ $field['label'] }}</dt>

          <dd class="m-0 min-w-0">
            <x-field-value
              :field="$field['field']"
              :value="$field['rawValue']"
              :formatted="$field['value']"
              :rows="$field['rows']"
              :reminder="$field['reminderMark']"
            />
          </dd>
        </div>
      @empty
        <p class="px-4.5 py-3 text-sm text-pretty text-muted">{{ __('This section asks nothing yet.') }}</p>
      @endforelse
    </dl>
  @endif
</x-panel>
